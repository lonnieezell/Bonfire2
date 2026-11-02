<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Email;

use Myth\Postal\Config\Mailer as PostalMailer;
use Myth\Postal\Email;
use Myth\Postal\SendResult;
use Myth\Postal\Transport\FailoverTransport;
use Myth\Postal\Transport\LogTransport;
use Myth\Postal\Transport\NullTransport;
use Throwable;

/**
 * What Settings -> Email shows about the configured mailers. Secrets are masked
 * here on the server, so the view never receives one. Listing builds no transport,
 * so a broken mailer config can't stop the page from rendering.
 */
final readonly class MailerDashboard
{
    /**
     * Setting names whose values are never shown.
     */
    private const SECRET_KEYS = '/pass|secret|key|token|credential|auth/i';

    /**
     * The credentials part of a URL. It runs up to the last @ of the value, because
     * a raw password can hold anything and hiding too much is the safe mistake.
     */
    private const URL_CREDENTIALS = '#([a-z][a-z0-9+.-]*://)(.*)@#is';

    /**
     * The shortest value worth removing from an error message.
     */
    private const MIN_SECRET_LENGTH = 4;

    private PostalMailer $config;

    public function __construct()
    {
        $this->config = config('Mailer');
    }

    /**
     * Every defined mailer, with its secrets masked.
     *
     * @return list<array{
     *     name: string,
     *     transport: string,
     *     isDefault: bool,
     *     bridged: bool,
     *     unknownTransport: bool,
     *     chain: list<array{name: string, defined: bool}>|null,
     *     loops: bool,
     *     brokenChain: bool,
     *     settings: list<array{key: string, value: string, masked: bool}>
     * }>
     */
    public function mailers(): array
    {
        $mailers = [];

        foreach (array_keys($this->config->mailers) as $name) {
            $name       = (string) $name;
            $definition = $this->definition($name);
            $chain      = $this->chain($name);
            $loops      = $this->loops($name);
            $transport  = $definition['transport'] ?? '';

            unset($definition['transport']);

            if ($chain !== null) {
                unset($definition['chain']);
            }

            $mailers[] = [
                'name'             => $name,
                'transport'        => is_string($transport) ? $transport : '',
                'isDefault'        => $name === $this->config->default,
                'bridged'          => $this->isBridged($name),
                'unknownTransport' => $this->transportClass($name) === null,
                'chain'            => $chain === null ? null : array_map(
                    fn (string $child): array => ['name' => $child, 'defined' => isset($this->config->mailers[$child])],
                    $chain,
                ),
                'loops'       => $loops,
                'brokenChain' => $chain !== null && ! $loops && ! $this->canSend($name),
                'settings'    => $this->rows($definition),
            ];
        }

        return $mailers;
    }

    /**
     * What's wrong with the mail setup as a whole.
     *
     * @return list<'bridge'|'brokenDefault'|'leftoverRows'|'nullDefault'|'undefinedDefault'>
     */
    public function warnings(): array
    {
        $warnings = [];

        if (LegacySettingsTransport::isActive()) {
            $warnings[] = 'bridge';
        } elseif (StoredTransportSettings::rows()->countAllResults() > 0) {
            $warnings[] = 'leftoverRows';
        }

        if (! isset($this->config->mailers[$this->config->default])) {
            $warnings[] = 'undefinedDefault';
        } elseif (! $this->canSend($this->config->default)) {
            $warnings[] = 'brokenDefault';
        } elseif ($this->delivery($this->config->default) === 'discarded') {
            $warnings[] = 'nullDefault';
        }

        return $warnings;
    }

    /**
     * What this mailer does with a message it reports as sent. A chain goes by the
     * first mailer it tries, since a later one's outcome can't be told in advance.
     *
     * @return 'discarded'|'logged'|'sent'
     */
    public function delivery(string $name): string
    {
        $first = $this->loops($name) ? null : ($this->chain($name)[0] ?? null);

        if ($first !== null) {
            return $this->delivery($first);
        }

        $class = (string) $this->transportClass($name);

        return match (true) {
            is_a($class, NullTransport::class, true) => 'discarded',
            is_a($class, LogTransport::class, true)  => 'logged',
            default                                  => 'sent',
        };
    }

    /**
     * Sends a fixed test message to $address through the named mailer. Failures
     * come back in the result, never as exceptions, with known secrets removed.
     */
    public function sendTest(string $name, string $address): SendResult
    {
        // The email.composing listener fills it in. Without one, transports would
        // report success for a message most servers reject.
        if (trim((string) setting('Email.fromEmail')) === '') {
            return SendResult::fail(lang('Email.previewNoFrom'));
        }

        return $this->deliver($name, (new Email())
            ->to($address)
            ->subject(lang('Email.testSubject'))
            ->text(lang('Email.testBody')));
    }

    /**
     * Sends the email through the named mailer, refusing one that is not defined
     * or whose chain loops. Failures come back in the result, never as exceptions,
     * with known secrets removed.
     */
    public function deliver(string $name, Email $email): SendResult
    {
        if (! isset($this->config->mailers[$name])) {
            return SendResult::fail(lang('Email.previewUnknownMailer'));
        }

        // Postal would recurse until PHP dies, which nothing here could catch.
        if ($this->loops($name)) {
            return SendResult::fail(lang('Email.mailerLoops'));
        }

        try {
            $result = service('mailer')->mailer($name)->send($email);
        } catch (Throwable $e) {
            return SendResult::fail($this->scrub($name, $e->getMessage()));
        }

        return $result->error === null ? $result : SendResult::fail($this->scrub($name, $result->error));
    }

    /**
     * Removes this mailer's secrets, and those of its failover children, from a
     * message. It can't recognise one a transport has encoded, and it leaves very
     * short values, such as a TTL or an on/off word, which would only garble it.
     */
    public function scrub(string $name, string $message): string
    {
        $secrets = array_filter($this->secrets($name), static fn (string $secret): bool => strlen($secret) >= self::MIN_SECRET_LENGTH);

        // Longest first, so a secret holding a shorter one goes whole.
        usort($secrets, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return str_replace($secrets, '***', $message);
    }

    /**
     * @return list<string>
     */
    private function secrets(string $name): array
    {
        $secrets = $this->secretsIn($this->definition($name));

        // Walking a looping chain would never end, and its sends are refused anyway.
        if (! $this->loops($name)) {
            foreach ($this->chain($name) ?? [] as $child) {
                $secrets = [...$secrets, ...$this->secrets($child)];
            }
        }

        return $secrets;
    }

    /**
     * @param array<array-key, mixed> $settings
     *
     * @return list<string>
     */
    private function secretsIn(array $settings): array
    {
        $secrets = [];

        foreach ($settings as $key => $value) {
            // Postal's authType only names a method, such as login: a word errors use.
            if ($key === 'authType') {
                continue;
            }

            if ($this->isSecretKey($key)) {
                $leaves = [$value];

                array_walk_recursive($leaves, static function (mixed $leaf) use (&$secrets): void {
                    if (is_string($leaf) || is_int($leaf) || is_float($leaf)) {
                        $secrets[] = (string) $leaf;
                    }
                });
            } elseif (is_array($value)) {
                $secrets = [...$secrets, ...$this->secretsIn($value)];
            } elseif (is_string($value) && preg_match_all(self::URL_CREDENTIALS, $value, $matches) > 0) {
                foreach ($matches[2] as $credentials) {
                    $password  = explode(':', $credentials, 2)[1] ?? $credentials;
                    $secrets[] = $credentials;
                    $secrets[] = rawurldecode($credentials);
                    $secrets[] = $password;
                    $secrets[] = rawurldecode($password);
                }
            }
        }

        return $secrets;
    }

    /**
     * Flattens settings to one row per value, with dotted keys.
     *
     * @param array<array-key, mixed> $settings
     *
     * @return list<array{key: string, value: string, masked: bool}>
     */
    private function rows(array $settings, string $prefix = ''): array
    {
        $rows = [];

        foreach ($settings as $key => $value) {
            $path = $prefix . $key;

            if ($this->isSecretKey($key) || is_object($value)) {
                $rows[] = ['key' => $path, 'value' => in_array($value, [null, '', [], false], true) ? 'empty' : 'set', 'masked' => true];
            } elseif (is_array($value)) {
                $rows = [...$rows, ...$this->rows($value, $path . '.')];
            } else {
                $rows[] = ['key' => $path, 'value' => $this->display($value), 'masked' => false];
            }
        }

        return $rows;
    }

    private function isSecretKey(int|string $key): bool
    {
        return preg_match(self::SECRET_KEYS, (string) $key) === 1;
    }

    private function display(mixed $value): string
    {
        return match (true) {
            is_string($value) => (string) preg_replace(self::URL_CREDENTIALS, '$1***@', $value),
            is_bool($value)   => $value ? 'true' : 'false',
            $value === null   => 'null',
            default           => (string) $value,
        };
    }

    /**
     * The settings a mailer really sends with: the bridge's come from its resolved
     * settings, not its own config entry.
     *
     * @return array<array-key, mixed>
     */
    private function definition(string $name): array
    {
        if ($this->isBridged($name)) {
            return LegacySettingsTransport::resolvedMailer();
        }

        return $this->entry($name);
    }

    /**
     * @return array<array-key, mixed> Empty if the mailer is undefined or malformed.
     */
    private function entry(string $name): array
    {
        $entry = $this->config->mailers[$name] ?? [];

        return is_array($entry) ? $entry : [];
    }

    private function isBridged(string $name): bool
    {
        return $this->transportClass($name) === LegacySettingsTransport::class;
    }

    /**
     * @return class-string|null Null if the mailer's transport isn't registered.
     */
    private function transportClass(string $name): ?string
    {
        $transport = $this->entry($name)['transport'] ?? null;

        return is_string($transport) ? ($this->config->transports[$transport] ?? null) : null;
    }

    /**
     * The child mailers of a failover mailer, or null for any other kind.
     *
     * @return list<string>|null
     */
    private function chain(string $name): ?array
    {
        if (! is_a((string) $this->transportClass($name), FailoverTransport::class, true)) {
            return null;
        }

        $chain = $this->entry($name)['chain'] ?? [];

        return is_array($chain) ? array_values(array_map(strval(...), array_filter($chain, is_scalar(...)))) : [];
    }

    /**
     * Whether Postal can build this mailer: it resolves every child up front, so
     * one bad child stops the whole chain, even when an earlier one would work.
     */
    private function canSend(string $name): bool
    {
        if ($this->transportClass($name) === null || $this->loops($name)) {
            return false;
        }

        $chain = $this->chain($name);

        if ($chain === []) {
            return false;
        }

        foreach ($chain ?? [] as $child) {
            if (! $this->canSend($child)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Whether resolving this mailer's chain would come back to a mailer on the way.
     *
     * @param list<string> $path
     */
    private function loops(string $name, array $path = []): bool
    {
        if (in_array($name, $path, true)) {
            return true;
        }

        foreach ($this->chain($name) ?? [] as $child) {
            if ($this->loops($child, [...$path, $name])) {
                return true;
            }
        }

        return false;
    }
}
