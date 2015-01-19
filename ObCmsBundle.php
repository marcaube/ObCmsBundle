<?php

namespace Ob\CmsBundle;

use Ob\CmsBundle\DependencyInjection\Compiler\AdminCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class ObCmsBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AdminCompilerPass());
    }
}
