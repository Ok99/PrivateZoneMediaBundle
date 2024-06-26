<?php

namespace Ok99\PrivateZoneCore\MediaBundle\Entity;

use Ok99\PrivateZoneCore\UserBundle\Entity\User;
use Sonata\MediaBundle\Entity\BaseMedia as BaseMedia;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="media__media")
 * @ORM\Entity(repositoryClass="Ok99\PrivateZoneCore\MediaBundle\Entity\Repository\MediaRepository")
 */
class Media extends BaseMedia
{
    /**
     * @var integer $id
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="lang", type="string", length=20, nullable=true)
     */
    protected $lang;

    /**
     * @ORM\OrderBy({"position" = "ASC"})
     * @ORM\OneToMany(targetEntity="GalleryHasMedia", mappedBy="media", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $galleryHasMedias;

    /**
     * @var string|null
     * @ORM\Column(name="link", type="string", length=255, nullable=true)
     */
    protected $link;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Ok99\PrivateZoneCore\ClassificationBundle\Entity\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $category;

    /**
     * @ORM\ManyToMany(targetEntity="Ok99\PrivateZoneCore\UserBundle\Entity\User")
     * @ORM\JoinTable(name="media__media__users",
     *      joinColumns={@ORM\JoinColumn(name="media__media__id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="RESTRICT")})
     */
    private $allowedUsers;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_notifiable", type="boolean")
     */
    private $isNotifiable = false;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="notification_sent_at", type="datetime", nullable=true)
     */
    private $notificationSentAt;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Ok99\PrivateZoneCore\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by_id", referencedColumnName="id", onDelete="SET NULL")
     */
    private $createdBy;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Ok99\PrivateZoneCore\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="updated_by_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    private $updatedBy;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->galleryHasMedias = new \Doctrine\Common\Collections\ArrayCollection();
        $this->allowedUsers = new \Doctrine\Common\Collections\ArrayCollection();
        $this->enabled = true;
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
     * Add galleryHasMedias
     *
     * @param \Ok99\PrivateZoneCore\MediaBundle\Entity\GalleryHasMedia $galleryHasMedias
     * @return Media
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
     * Set lang
     *
     * @param string $lang
     * @return Media
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * Get lang
     *
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @return string|null
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string|null $link
     */
    public function setLink($link): self
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Add allowedUser
     *
     * @param \Ok99\PrivateZoneCore\UserBundle\Entity\User $allowedUser
     * @return Media
     */
    public function addAllowedUser(\Ok99\PrivateZoneCore\UserBundle\Entity\User $allowedUser)
    {
        $this->allowedUsers[] = $allowedUser;

        return $this;
    }

    /**
     * Remove allowedUser
     *
     * @param \Ok99\PrivateZoneCore\UserBundle\Entity\User $allowedUser
     */
    public function removeAllowedUser(\Ok99\PrivateZoneCore\UserBundle\Entity\User $allowedUser)
    {
        $this->allowedUsers->removeElement($allowedUser);
    }

    /**
     * Get allowedUsers
     *
     * @return User[]
     */
    public function getAllowedUsers()
    {
        $allowedUsers = $this->allowedUsers->getValues();

        if ($allowedUsers) {
            $collator = new \Collator('cs_CZ');
            $collator->sort($allowedUsers);
        }

        return $allowedUsers;
    }

    /**
     * Set createdBy
     *
     * @param User|null $createdBy
     * @return Media
     */
    public function setCreatedBy(User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return User|int
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set updatedBy
     *
     * @param User|null $updatedBy
     * @return Media
     */
    public function setUpdatedBy(User $updatedBy = null)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * Get updatedBy
     *
     * @return User|int
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * Set notificationSentAt
     *
     * @param \DateTime $notificationSentAt
     * @return Media
     */
    public function setNotificationSentAt($notificationSentAt)
    {
        $this->notificationSentAt = $notificationSentAt;

        return $this;
    }

    /**
     * Get notificationSentAt
     *
     * @return \DateTime|null
     */
    public function getNotificationSentAt()
    {
        return $this->notificationSentAt;
    }

    /**
     * Set isNotifiable
     *
     * @param boolean $isNotifiable
     * @return Media
     */
    public function setIsNotifiable(bool $isNotifiable)
    {
        $this->isNotifiable = $isNotifiable;

        return $this;
    }

    /**
     * Get isNotifiable
     *
     * @return boolean
     */
    public function getIsNotifiable()
    {
        return $this->isNotifiable;
    }
}
