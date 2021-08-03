<?php

namespace Bonfire\Settings;

abstract class BaseHandler
{
    /**
     * Returns a single value from the handler, if stored.
     *
     * @param string $class
     * @param string $key
     *
     * @return mixed
     */
    abstract public function get(string $class, string $key);

    /**
     * If the Handler supports saving values, it
     * MUST override this method to provide that functionality.
     * Not all Handlers will support writing values.
     *
     * @param string $key
     * @param null   $value
     *
     * @return mixed
     */
    public function set(string $class, string $key, $value=null)
    {
        throw new \RuntimeException('Set method not implemented for current Settings handler.');
    }

    /**
     * Takes care of converting some item types so they can be safely
     * stored and re-hydrated into the config files.
     *
     * @param $value
     *
     * @return string|mixed
     */
    protected function prepareValue($value)
    {
        if ($value === true) {
            return ':true';
        }

        if ($value === false) {
            return ':false';
        }

        if (is_array($value) || is_object($value)) {
            return serialize($value);
        }

        return $value;
    }

    /**
     * Handles some special case conversions that
     * data might have been saved as, such as booleans
     * and serialized data.
     *
     * @param $value
     *
     * @return bool|mixed
     */
    protected function parseValue($value)
    {
        // :true -> boolean
        if ($value === ':true') {
            return true;
        }

        // :false -> boolean
        if ($value === ':false') {
            return false;
        }

        // Serialized?
        if ($this->isSerialized($value)) {
            return unserialize($value);
        }

        return $value;
    }

    /**
     * Checks to see if an object is serialized and correctly formatted.
     *
     * Taken from Wordpress core functions.
     *
     * @param      $data
     * @param bool $strict  Whether to be strict about the end of the string.
     *
     * @return bool
     */
    protected function isSerialized($data, $strict=true ): bool
    {
        // If it isn't a string, it isn't serialized.
        if ( ! is_string( $data ) ) {
            return false;
        }
        $data = trim( $data );
        if ( 'N;' === $data ) {
            return true;
        }
        if ( strlen( $data ) < 4 ) {
            return false;
        }
        if ( ':' !== $data[1] ) {
            return false;
        }
        if ( $strict ) {
            $lastc = substr( $data, -1 );
            if ( ';' !== $lastc && '}' !== $lastc ) {
                return false;
            }
        } else {
            $semicolon = strpos( $data, ';' );
            $brace     = strpos( $data, '}' );
            // Either ; or } must exist.
            if ( false === $semicolon && false === $brace ) {
                return false;
            }
            // But neither must be in the first X characters.
            if ( false !== $semicolon && $semicolon < 3 ) {
                return false;
            }
            if ( false !== $brace && $brace < 4 ) {
                return false;
            }
        }
        $token = $data[0];
        switch ( $token ) {
            case 's':
                if ( $strict ) {
                    if ( '"' !== substr( $data, -2, 1 ) ) {
                        return false;
                    }
                } elseif ( false === strpos( $data, '"' ) ) {
                    return false;
                }
            // Or else fall through.
            case 'a':
            case 'O':
                return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';
                return (bool) preg_match( "/^{$token}:[0-9.E+-]+;$end/", $data );
        }
        return false;
    }
}
