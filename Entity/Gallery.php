<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Gedmo\Mapping\Annotation as Gedmo;
use Cocur\Slugify\Slugify;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="media__gallery")
 */
class Gallery
{

    /**
     * @var integer $id
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Site
     *
     * @ORM\ManyToOne(targetEntity="Ok99\PrivateZoneCore\PageBundle\Entity\Site", cascade={"persist"})
     * @ORM\JoinColumn(name="site_id", nullable=true)
     */
    private $site;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $name;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\OrderBy({"position" = "ASC"})
     * @ORM\OneToMany(targetEntity="GalleryHasMedia", mappedBy="gallery", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $galleryHasMedias;

    /**
     * @Gedmo\Translatable
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $slug;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $enabled = true;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\OneToMany(targetEntity="GalleryTranslation", mappedBy="object", indexBy="locale", cascade={"persist","remove"}, orphanRemoval=true)
     * @Assert\Valid
     */
    private $translations;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->galleryHasMedias = new \Doctrine\Common\Collections\ArrayCollection();
        $this->translations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }



    /**
     * Get site
     *
     * @return Site
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * Set site
     *
     * @param Site $site
     * @return Context
     */
    public function setSite($site)
    {
        $this->site = $site;
        return $this;
    }

    /**
     * Add galleryHasMedias
     *
     * @param \Ok99\PrivateZoneCore\MediaBundle\Entity\GalleryHasMedia $galleryHasMedias
     * @return Gallery
     */
    public function addGalleryHasMedia(\Ok99\PrivateZoneCore\MediaBundle\Entity\GalleryHasMedia $galleryHasMedias)
    {
        $this->galleryHasMedias[] = $galleryHasMedias;

        return $this;
    }

    /**
     * Remove galleryHasMedias
     *
     * @param \Ok99\PrivateZoneCore\MediaBundle\Entity\GalleryHasMedia $galleryHasMedias
     */
    public function removeGalleryHasMedia(\Ok99\PrivateZoneCore\MediaBundle\Entity\GalleryHasMedia $galleryHasMedias)
    {
        $this->galleryHasMedias->removeElement($galleryHasMedias);
    }

    /**
     * Get galleryHasMedias
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGalleryHasMedias()
    {
        return $this->galleryHasMedias;
    }

    /**
     * @param mixed $galleryHasMedias
     * @return $this
     */
    public function setGalleryHasMedias($galleryHasMedias)
    {
        $this->galleryHasMedias = $galleryHasMedias;
        return $this;
    }

    public function getTranslations()
    {
        return $this->translations;
    }

    public function addTranslation(GalleryTranslation $translation)
    {
        if (!$this->translations->contains($translation)) {
            if ($translation->getName()) {
                $this->translations[] = $translation;
                $translation->setObject($this);
            }
        }

        return $this;
    }

    public function removeTranslation(GalleryTranslation $translation)
    {
        if ($this->translations->contains($translation)) {
            $this->translations->removeElement($translation);
        }
        return $this;
    }

    /**
     * Get name
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param mixed $name
     * @return Gallery
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get description
     *
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set description
     *
     * @param mixed $description
     * @return Gallery
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get slug
     *
     * @return mixed
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set slug
     *
     * @param mixed $slug
     * @return Gallery
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * Get enabled
     *
     * @return mixed
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Set enabled
     *
     * @param mixed $enabled
     * @return Gallery
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return mixed
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set updatedAt
     *
     * @param mixed $updatedAt
     * @return Gallery
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    /**
     * Get createdAt
     *
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set createdAt
     *
     * @param mixed $createdAt
     * @return Gallery
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
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

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @Assert\Callback
     */
    public function isValid(ExecutionContextInterface $context)
    {
        $valid = false;
        foreach ($this->translations as $trans) {
            if ($trans->getName()) {
                $valid = true;
            }
        }

        if (!$valid) {
            $context->buildViolation('Musíte vyplnit alespoň jednu jazykovou verzi.')
                ->atPath('translations')
                ->addViolation();
        }
    }
}
