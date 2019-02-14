<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\PortfolioBundle\Entity;

use Claroline\AppBundle\Entity\Identifier\Uuid;
use Claroline\CoreBundle\Entity\Group;
use Claroline\CoreBundle\Entity\Tab\HomeTab;
use Claroline\CoreBundle\Entity\User;
use Claroline\TeamBundle\Entity\Team;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="claro_portfolio")
 */
class Portfolio
{
    use Uuid;

    const VISIBILITY_ME = 'me';
    const VISIBILITY_USERS = 'users';
    const VISIBILITY_PLATFORM_USERS = 'platform_users';
    const VISIBILITY_EVERYBODY = 'eveybody';

    const TAB_TYPE_PORTFOLIO = 'portfolio';

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=128, nullable=false)
     */
    protected $title;

    /**
     * @ORM\Column(type="string", length=128, unique=true, nullable=true)
     */
    protected $slug;

    /**
     * @ORM\Column(type="string", name="visibility", nullable=false)
     */
    protected $visibility = self::VISIBILITY_ME;

    /**
     * @ORM\ManyToOne(targetEntity="Claroline\CoreBundle\Entity\User")
     * @ORM\JoinColumn(name="owner_id", nullable=false)
     */
    protected $owner;

    /**
     * @ORM\ManyToMany(targetEntity="Claroline\CoreBundle\Entity\Tab\HomeTab")
     * @ORM\JoinTable(name="claro_portfolio_tabs")
     */
    private $tabs;

    /**
     * @ORM\ManyToMany(targetEntity="Claroline\CoreBundle\Entity\User")
     * @ORM\JoinTable(name="claro_portfolio_users")
     */
    private $users;

    /**
     * @ORM\ManyToMany(targetEntity="Claroline\CoreBundle\Entity\Group")
     * @ORM\JoinTable(name="claro_portfolio_groups")
     */
    private $groups;

    /**
     * @ORM\ManyToMany(targetEntity="Claroline\TeamBundle\Entity\Team")
     * @ORM\JoinTable(name="claro_portfolio_teams")
     */
    private $teams;

    /**
     * @ORM\OneToMany(
     *     targetEntity="Claroline\PortfolioBundle\Entity\PortfolioComment",
     *     mappedBy="portfolio",
     *     cascade={"persist"}
     * )
     */
    protected $comments;

    public function __construct()
    {
        $this->refreshUuid();
        $this->tabs = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->groups = new ArrayCollection();
        $this->teams = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    public function getVisibility()
    {
        return $this->visibility;
    }

    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner(User $owner)
    {
        $this->owner = $owner;
    }

    public function getTabs()
    {
        return $this->tabs;
    }

    public function addTab(HomeTab $tab)
    {
        if (!$this->tabs->contains($tab)) {
            $this->tabs->add($tab);
        }

        return $this;
    }

    public function removeTab(HomeTab $tab)
    {
        if ($this->tabs->contains($tab)) {
            $this->tabs->removeElement($tab);
        }

        return $this;
    }

    public function emptyTabs()
    {
        $this->tabs->clear();
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function addUser(User $user)
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user)
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
        }

        return $this;
    }

    public function emptyUsers()
    {
        $this->users->clear();
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function addGroup(Group $group)
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
        }

        return $this;
    }

    public function removeGroup(Group $group)
    {
        if ($this->groups->contains($group)) {
            $this->groups->removeElement($group);
        }

        return $this;
    }

    public function emptyGroups()
    {
        $this->groups->clear();
    }

    public function getTeams()
    {
        return $this->teams;
    }

    public function addTeam(Team $team)
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
        }

        return $this;
    }

    public function removeTeam(Team $team)
    {
        if ($this->teams->contains($team)) {
            $this->teams->removeElement($team);
        }

        return $this;
    }

    public function emptyTeams()
    {
        $this->teams->clear();
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function addComment(PortfolioComment $comment)
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
        }

        return $this;
    }

    public function removeComment(PortfolioComment $comment)
    {
        if ($this->comments->contains($comment)) {
            $this->comments->removeElement($comment);
        }

        return $this;
    }

    public function emptyComments()
    {
        $this->comments->clear();
    }
}
