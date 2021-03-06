<?php

/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Claroline\AuthenticationBundle\Configuration;

class OauthConfiguration
{
    private $clientId;
    private $clientSecret;
    private $clientActive;
    private $clientTenantDomain = null;
    private $clientVersion = null;
    private $clientForceReauthenticate = false;
    private $scope = null;
    private $authorizationUrl = null;
    private $accessTokenUrl = null;
    private $infosUrl = null;
    private $pathsLogin = null;
    private $pathsEmail = null;
    private $displayName = null;

    public function __construct($id, $secret, $active, $forceReauthenticate = false, $domain = null, $version = null)
    {
        $this->clientId = $id;
        $this->clientSecret = $secret;
        $this->clientActive = $active;
        $this->clientTenantDomain = $domain;
        $this->clientVersion = $version;
        $this->clientForceReauthenticate = true === $forceReauthenticate;
    }

    /**
     * @param int $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return int
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param int $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return int
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param bool $clientActive
     */
    public function setClientActive($clientActive)
    {
        $this->clientActive = $clientActive;
    }

    /**
     * @return bool
     */
    public function isClientActive()
    {
        return $this->clientActive;
    }

    /**
     * @return mixed
     */
    public function getClientTenantDomain()
    {
        return $this->clientTenantDomain;
    }

    /**
     * @param mixed $clientTenantDomain
     *
     * @return $this
     */
    public function setClientTenantDomain($clientTenantDomain)
    {
        $this->clientTenantDomain = $clientTenantDomain;

        return $this;
    }

    public function getClientVersion()
    {
        return $this->clientVersion;
    }

    /**
     * @param null $clientVersion
     *
     * @return $this
     */
    public function setClientVersion($clientVersion)
    {
        $this->clientVersion = $clientVersion;

        return $this;
    }

    /**
     * @return bool
     */
    public function isClientForceReauthenticate()
    {
        return true === $this->clientForceReauthenticate;
    }

    /**
     * @param bool $clientForceReauthenticate
     *
     * @return $this
     */
    public function setClientForceReauthenticate($clientForceReauthenticate)
    {
        $this->clientForceReauthenticate = $clientForceReauthenticate;

        return $this;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param string $scope
     *
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthorizationUrl()
    {
        return $this->authorizationUrl;
    }

    /**
     * @param string $authorizationUrl
     *
     * @return $this
     */
    public function setAuthorizationUrl($authorizationUrl)
    {
        $this->authorizationUrl = $authorizationUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getInfosUrl()
    {
        return $this->infosUrl;
    }

    /**
     * @param string $infosUrl
     *
     * @return $this
     */
    public function setInfosUrl($infosUrl)
    {
        $this->infosUrl = $infosUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return $this->accessTokenUrl;
    }

    /**
     * @param string $accessTokenUrl
     *
     * @return $this
     */
    public function setAccessTokenUrl($accessTokenUrl)
    {
        $this->accessTokenUrl = $accessTokenUrl;

        return $this;
    }

    /**
     * @return string
     */
    public function getPathsLogin()
    {
        return $this->pathsLogin;
    }

    /**
     * @param string $pathsLogin
     *
     * @return $this
     */
    public function setPathsLogin($pathsLogin)
    {
        $this->pathsLogin = $pathsLogin;

        return $this;
    }

    /**
     * @return string
     */
    public function getPathsEmail()
    {
        return $this->pathsEmail;
    }

    /**
     * @param string $pathsEmail
     *
     * @return $this
     */
    public function setPathsEmail($pathsEmail)
    {
        $this->pathsEmail = $pathsEmail;

        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @param string $displayName
     *
     * @return $this
     */
    public function setDisplayName($displayName)
    {
        $this->displayName = $displayName;

        return $this;
    }

    public static function resourceOwners()
    {
        $resourceOwners = ['Dropbox', 'Facebook', 'Twitter', 'Github', 'Google', 'Linkedin', 'Windows Live', 'Office 365', 'Generic'];

        return $resourceOwners;
    }
}
