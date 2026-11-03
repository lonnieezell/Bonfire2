<?php

namespace Bonfire\Commands\Install;

use Bonfire\Assets\Config\Assets;
use stdClass;

/**
 * The text edits `bf:install` makes to the app's files.
 *
 * Each method takes a file's content and returns an Edit: the new content,
 * or the text it expected but could not find. Nothing here touches the
 * disk, so the installer decides when to read and write, and the edits can
 * be tested against the text of any framework version.
 */
final class ContentEdits
{
    /**
     * Replaces `$find` with `$replace`. An edit that was applied already is
     * left alone, so a second run does not repeat it.
     */
    public static function replaceText(string $content, string $find, string $replace): Edit
    {
        $found   = str_contains($content, $find);
        $applied = $replace !== '' && str_contains($content, $replace);

        // When the replacement holds the text it replaces, finding that text
        // proves nothing, so the replacement being there means it is done.
        if ($found && ! ($applied && str_contains($replace, $find))) {
            return new Edit($content, str_replace($find, $replace, $content));
        }

        return $applied
            ? new Edit($content, $content)
            : new Edit($content, $content, [$find]);
    }

    /**
     * Sets `$key` in an .env file by uncommenting the template's `# key =`
     * line. A key that is set already keeps its value, so a second run does
     * not change an existing encryption key or database choice.
     */
    public static function setEnv(string $env, string $key, string $value): Edit
    {
        $quoted = preg_quote($key, '/');

        if (preg_match('/^' . $quoted . '\s*=/m', $env) === 1) {
            return new Edit($env, $env);
        }

        $output = preg_replace_callback(
            '/^#\s*' . $quoted . '\s*=[^\r\n]*/m',
            static fn (): string => "{$key} = {$value}",
            $env,
            1,
            $count,
        );

        return $count === 0
            ? new Edit($env, $env, ["# {$key} ="])
            : new Edit($env, $output);
    }

    /**
     * Adds `app.indexPage = ''` above `app.baseURL`, so URLs have no index.php.
     */
    public static function removeIndexPage(string $env): Edit
    {
        if (preg_match('/^app\.indexPage\s*=/m', $env) === 1) {
            return new Edit($env, $env);
        }

        $output = preg_replace('/^app\.baseURL/m', "app.indexPage = ''" . PHP_EOL . '$0', $env, 1, $count);

        return $count === 0
            ? new Edit($env, $env, ['app.baseURL'])
            : new Edit($env, $output);
    }

    /**
     * Adds the SQLite3 options to the `$default` connection of Config\Database,
     * right after its `port`.
     */
    public static function sqliteDatabaseConfig(string $config): Edit
    {
        // The first `port` is the `$default` connection's. The framework's
        // `$tests` connection further down has foreignKeys too, so only the
        // line right after that port tells us this was done already.
        if (preg_match("/^([ \t]*)'port'[ \t]*=>[ \t]*\\d+,(\\R)/m", $config, $match, PREG_OFFSET_CAPTURE) !== 1) {
            return new Edit($config, $config, ["'port' => 3306,"]);
        }

        [$line, $offset] = $match[0];
        $indent          = $match[1][0];
        $eol             = $match[2][0];
        $after           = $offset + strlen($line);

        if (preg_match("/\\G[ \t]*'foreignKeys'/", $config, offset: $after) === 1) {
            return new Edit($config, $config);
        }

        return new Edit($config, substr_replace(
            $config,
            $indent . "'foreignKeys' => true," . $eol . $indent . "'busyTimeout' => 1000," . $eol,
            $after,
            0,
        ));
    }

    /**
     * Sets the `$helpers` list of Config\Autoload to `$helpers`.
     *
     * @param list<string> $helpers
     */
    public static function autoloadHelpers(string $content, array $helpers): Edit
    {
        $list   = "['" . implode("', '", $helpers) . "'];";
        $output = preg_replace_callback(
            '/^([ \t]*public (?:array )?\$helpers\s*=\s*)\[[^\]]*];/mu',
            static fn (array $m): string => $m[1] . $list,
            $content,
            1,
            $count,
        );

        return $count === 0
            ? new Edit($content, $content, ['public $helpers = [...];'])
            : new Edit($content, $output);
    }

    /**
     * Adds `$command` to the `post-update-cmd` scripts of a composer.json.
     */
    public static function composerUpdateScript(string $json, string $command): Edit
    {
        $data = json_decode($json);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new Edit($json, $json, ['valid JSON (' . json_last_error_msg() . ')']);
        }

        $scripts = $data instanceof stdClass ? ($data->scripts ?? new stdClass()) : null;

        if (! $data instanceof stdClass || ! $scripts instanceof stdClass) {
            return new Edit($json, $json, ['a "scripts" object']);
        }

        $commands = $scripts->{'post-update-cmd'} ?? [];
        $commands = is_array($commands) ? $commands : [$commands];

        if (in_array($command, $commands, true)) {
            return new Edit($json, $json);
        }

        $commands[]                   = $command;
        $scripts->{'post-update-cmd'} = $commands;
        $data->scripts                = $scripts;

        $output = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $output === false
            ? new Edit($json, $json, ['JSON that can be written back (' . json_last_error_msg() . ')'])
            : new Edit($json, $output . "\n");
    }

    /**
     * Turns the source of a Bonfire config class into the app's copy: it moves
     * into the Config namespace and extends the Bonfire class it was copied from.
     */
    public static function publishedConfig(string $source, string $className): Edit
    {
        $namespace    = explode('\\', $className);
        $rawClassName = array_pop($namespace);
        $namespace    = implode('\\', $namespace);

        $replace = [
            $namespace                                                      => 'Config',
            'BaseConfig'                                                    => 'Bonfire' . $rawClassName,
            'use CodeIgniter\Config\BaseConfig;'                            => "use {$className} as Bonfire{$rawClassName};",
            'use CodeIgniter\Shield\Config\Auth as ShieldAuth;'             => 'use Bonfire\Auth\Config\Auth as BonfireAuth;',
            'use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;' => 'use Bonfire\Auth\Config\AuthGroups as BonfireAuthGroups;',
            'use CodeIgniter\Shield\Config\AuthToken as ShieldAuthToken;'   => 'use Bonfire\Auth\Config\AuthToken as BonfireAuthToken;',
            'use Tatter\Alerts\Config\Alerts as AlertsConfig;'              => 'use Bonfire\Config\Alerts as BonfireAlerts;',
            'class Alerts extends AlertsConfig'                             => 'class Alerts extends BonfireAlerts',
            "class {$rawClassName} extends Shield{$rawClassName}"           => "class {$rawClassName} extends Bonfire{$rawClassName}",
        ];

        $missing = [];

        if ($className === Assets::class) {
            $theme = "__DIR__ . '/../../../themes";

            if (! str_contains($source, $theme)) {
                $missing[] = $theme;
            }

            $replace[$theme] = "ROOTPATH . '/themes";
        }

        $output = strtr($source, $replace);

        if (! str_contains($output, 'namespace Config;')) {
            $missing[] = "namespace {$namespace};";
        }

        // A class with a parent has to end up extending the Bonfire class
        // it was copied from, or it would silently lose Bonfire's settings.
        $extends = '^class ' . preg_quote($rawClassName, '/') . ' extends ';

        if (preg_match("/{$extends}/m", $source) === 1) {
            $bonfire = 'Bonfire' . $rawClassName;

            if (preg_match("/{$extends}{$bonfire}\\b/m", $output) !== 1) {
                $missing[] = "class {$rawClassName} extends {$bonfire}";
            }

            if (! str_contains($output, "use {$className} as {$bonfire};")) {
                $missing[] = "use {$className} as {$bonfire};";
            }
        }

        return new Edit($source, $output, $missing);
    }
}
