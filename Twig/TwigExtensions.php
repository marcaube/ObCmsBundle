<?php

namespace Ob\CmsBundle\Twig;

class TwigExtensions extends \Twig_Extension
{
    private $configs;

    /**
     * @param array $configs
     */
    public function __construct($configs)
    {
        $this->configs = $configs;
    }

    public function getFunctions()
    {
        return array(
            'varType'  => new \Twig_SimpleFunction('varType', array($this, 'varType')),
            'varClass' => new \Twig_SimpleFunction('varClass', array($this, 'varClass')),
        );
    }

    public function getGlobals()
    {
        return array(
            'templates' => $this->configs['templates'],
            'logo'      => $this->configs['logo']
        );
    }

    public function varType($var)
    {
        return gettype($var);
    }

    public function varClass($var)
    {
        return get_class($var);
    }

    public function getName()
    {
        return 'ob_cms_twig_extension';
    }
}
