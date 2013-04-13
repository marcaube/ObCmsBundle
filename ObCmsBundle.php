<?php

namespace Ob\CmsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Ob\CmsBundle\DependencyInjection\Compiler\AdminCompilerPass;

class ObCmsBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AdminCompilerPass());
    }
}
