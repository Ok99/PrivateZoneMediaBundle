<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation;
use Gedmo\Mapping\Annotation as Gedmo;
use Ok99\PrivateZoneCore\ClassificationBundle\Entity\Tag;
use Cocur\Slugify\Slugify;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="media__gallery__translation", uniqueConstraints={
 *    @ORM\UniqueConstraint(name="gallery_translation_unique_idx", columns={"locale", "object_id"})
 * })
 */
class GalleryTranslation extends AbstractTranslation
{

    /**
     * @var integer $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $locale
     *
     * @ORM\Column(type="string", length=8)
     */
    protected $locale;

    /**
     * @ORM\ManyToOne(targetEntity="Gallery", inversedBy="translations")
     * @ORM\JoinColumn(name="object_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $object;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $slug;

    public function __construct($locale = null, $name = null, $description = null)
    {
        $this->locale = $locale;
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * {@inheritdoc}
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * {@inheritdoc}
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set locale
     *
     * @param string $locale
     * @return PostTranslation
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Get locale
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set object
     *
     * @param \Ok99\PrivateZoneCore\NewsBundle\Entity\Post $object
     * @return PostTranslation
     */
    public function setObject($object)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Get object
     *
     * @return \Ok99\PrivateZoneCore\NewsBundle\Entity\Post
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function generateSlug()
    {
        $slugify = new Slugify();
        $this->setSlug($slugify->slugify($this->getName()));
    }
}
