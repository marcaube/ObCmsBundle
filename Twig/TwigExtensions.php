<?php
namespace Ob\CmsBundle\Twig;

class TwigExtensions extends \Twig_Extension
{
    public function getFunctions()
    {
        return array(
            'varType'  => new \Twig_Function_Method($this, 'varType'),
            'varClass' => new \Twig_Function_Method($this, 'varClass')
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
