<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\MediaBundle\Block;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Block\Service\AbstractBlockService;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\CoreBundle\Model\Metadata;
use Sonata\MediaBundle\Model\GalleryManagerInterface;
use Sonata\MediaBundle\Provider\Pool;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GalleryListBlockService extends AbstractBlockService
{
    /**
     * @var GalleryManagerInterface
     */
    protected $galleryManager;

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * @param string                  $name
     * @param EngineInterface         $templating
     * @param GalleryManagerInterface $galleryManager
     * @param Pool                    $pool
     */
    public function __construct($name, EngineInterface $templating, GalleryManagerInterface $galleryManager, Pool $pool)
    {
        parent::__construct($name, $templating);

        $this->galleryManager = $galleryManager;
        $this->pool = $pool;
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        $contextChoices = array();

        foreach ($this->pool->getContexts() as $name => $context) {
            $contextChoices[$name] = $name;
        }

        // NEXT_MAJOR: Keep FQCN when bumping Symfony requirement to 2.8+.
        if (method_exists('Symfony\Component\Form\AbstractType', 'getBlockPrefix')) {
            $immutableArrayType = 'Sonata\CoreBundle\Form\Type\ImmutableArrayType';
            $textType = 'Symfony\Component\Form\Extension\Core\Type\TextType';
            $integerType = 'Symfony\Component\Form\Extension\Core\Type\IntegerType';
            $choiceType = 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
        } else {
            $immutableArrayType = 'sonata_type_immutable_array';
            $textType = 'text';
            $integerType = 'integer';
            $choiceType = 'choice';
        }

        $formMapper->add('settings', $immutableArrayType, array(
            'keys' => array(
                array('title', $textType, array(
                    'label' => 'form.label_title',
                    'required' => false,
                )),
                array('number', $integerType, array(
                    'label' => 'form.label_number',
                    'required' => true,
                )),
                array('context', $choiceType, array(
                    'required' => true,
                    'label' => 'form.label_context',
                    'choices' => $contextChoices,
                )),
                array('mode', $choiceType, array(
                    'label' => 'form.label_mode',
                    'choices' => array(
                        'public' => 'form.label_mode_public',
                        'admin' => 'form.label_mode_admin',
                    ),
                )),
                array('order', $choiceType,  array(
                    'label' => 'form.label_order',
                    'choices' => array(
                        'name' => 'form.label_order_name',
                        'createdAt' => 'form.label_order_created_at',
                        'updatedAt' => 'form.label_order_updated_at',
                    ),
                )),
                array('sort', $choiceType, array(
                    'label' => 'form.label_sort',
                    'choices' => array(
                        'desc' => 'form.label_sort_desc',
                        'asc' => 'form.label_sort_asc',
                    ),
                )),
            ),
            'translation_domain' => 'SonataMediaBundle',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $context = $blockContext->getBlock()->getSetting('context');

        $criteria = array(
            'mode' => $blockContext->getSetting('mode'),
            'context' => $context,
        );

        $order = array(
            $blockContext->getSetting('order') => $blockContext->getSetting('sort'),
        );

        return $this->renderResponse($blockContext->getTemplate(), array(
            'context' => $blockContext,
            'settings' => $blockContext->getSettings(),
            'block' => $blockContext->getBlock(),
            'pager' => $this->galleryManager->getPager(
                $criteria,
                1,
                $blockContext->getSetting('number'),
                $order
            ),
        ), $response);
    }

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'number' => 15,
            'mode' => 'public',
            'order' => 'createdAt',
            'sort' => 'desc',
            'context' => false,
            'title' => 'Gallery List',
            'template' => 'SonataMediaBundle:Block:block_gallery_list.html.twig',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockMetadata($code = null)
    {
        return new Metadata($this->getName(), (!is_null($code) ? $code : $this->getName()), false, 'SonataMediaBundle', array(
            'class' => 'fa fa-picture-o',
        ));
    }
}
