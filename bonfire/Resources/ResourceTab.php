<?php

namespace Bonfire\Resources;

/**
 * Represents a single tab that should be displayed for a resource.
 * A resource is a User, a User Group, etc.
 */
class ResourceTab
{
    /**
     * The title displayed to the user.
     *
     * @var string
     */
    protected $title;

    /**
     * The URL used to link the tab to.
     *
     * @var string
     */
    protected $url;

    /**
     * The permission needed to view the tab
     *
     * @var string
     */
    protected $permission;

    /**
     * Constructor.
     *
     * Use $params to fill the values on creation.
     *
     * @param array|null $params
     */
    public function __construct(array $params=null)
    {
        if (is_array($params)) {
            foreach($params as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
    }

    /**
     * Magic getter
     *
     * @param string $key
     */
    public function __get(string $key)
    {
        if (property_exists($this, $key)) {
            return $this->$key;
        }
    }

    /**
     * Magic setting
     *
     * @param string $key
     * @param        $value
     */
    public function __set(string $key, $value)
    {
        if (property_exists($this, $key)) {
            $this->$key = $value;
        }
    }
}
