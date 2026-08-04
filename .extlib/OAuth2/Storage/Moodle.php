<?php

namespace OAuth2\Storage;

use OAuth2\OpenID\Storage\UserClaimsInterface;
use OAuth2\OpenID\Storage\AuthorizationCodeInterface as OpenIDAuthorizationCodeInterface;

/**
 * Simple PDO storage for all storage types
 *
 * NOTE: This class is meant to get users started
 * quickly. If your application requires further
 * customization, extend this class or create your own.
 *
 * NOTE: Passwords are stored in plaintext, which is never
 * a good idea.  Be sure to override this for your application
 *
 * @author Brent Shaffer <bshafs at gmail dot com>
 */
class Moodle implements
    AuthorizationCodeInterface,
    AccessTokenInterface,
    ClientCredentialsInterface,
    UserCredentialsInterface,
    RefreshTokenInterface,
    JwtBearerInterface,
    ScopeInterface,
    PublicKeyInterface,
    UserClaimsInterface,
    OpenIDAuthorizationCodeInterface {
    protected $config;

    public function __construct($connection, $config = []) {
        $this->config = $config;
    }

    /* OAuth2\Storage\ClientCredentialsInterface */
    public function checkClientCredentials($client_id, $client_secret = null) {
        global $DB;
        $client_secret_db = $DB->get_field('local_user_provisioning_clients', 'client_secret', ['client_id' => $client_id]);
        return $client_secret == $client_secret_db;
    }

    public function isPublicClient($client_id) {
        global $DB;
        $client = $DB->get_record('local_user_provisioning_clients', ['client_id' => $client_id]);
        if (!$client) {
            return false;
        }
        return empty($client->client_secret);
    }

    /* OAuth2\Storage\ClientInterface */
    public function getClientDetails($client_id) {
        global $DB;
        $client = $DB->get_record('local_user_provisioning_clients', ['client_id' => $client_id]);
        if (!$client) {
            return false;
        }

        $scope = 'SCIMv2';
        if (!empty($client->additionalscopes)) {
            $scope .= ' ' . $client->additionalscopes;
        }

        $oauthclient = [
            'client_id' => $client->client_id,
            'client_secret' => $client->client_secret,
            'redirect_uri' => null,
            'grant_types' => 'client_credentials authorization_code',
            'user_id' => $client->user_id,
            'scope' => $scope
        ];

        return $oauthclient;
    }

    public function setClientDetails($client_id, $client_secret = null, $redirect_uri = null, $grant_types = null, $scope = null, $user_id = null) {
        global $DB;
        if ($client = $DB->get_record('local_user_provisioning_clients', ['client_id' => $client_id])) {
            $scope = 'SCIMv2';
            if (!empty($client->additionalscopes)) {
                $scope .= ' ' . $client->additionalscopes;
            }
            $client->client_secret = $client_secret;
            $client->redirect_uri = null;
            $client->grant_types = 'client_credentials authorization_code';
            $client->scope = $scope;
            $DB->update_record('local_user_provisioning_clients', $client);
        } else {
            $client = new \StdClass();
            $client->client_secret = $client_secret;
            $client->redirect_uri = null;
            $client->grant_types = 'client_credentials authorization_code';
            $client->scope = 'SCIMv2';
            $client->client_id = $client_id;
            $DB->insert_record('local_user_provisioning_clients', $client);
        }

        return true;
    }

    public function checkRestrictedGrantType($client_id, $grant_type) {
        $details = $this->getClientDetails($client_id);
        if (isset($details['grant_types'])) {
            $grant_types = explode(' ', $details['grant_types']);

            return in_array($grant_type, (array)$grant_types, true);
        }

        // if grant_types are not defined, then none are restricted
        return true;
    }

    /* OAuth2\Storage\AccessTokenInterface */
    public function getAccessToken($oauth_token) {
        global $DB;
        $token = $DB->get_record('local_user_provisioning_accesstoken', ['access_token' => $oauth_token]);
        if (!$token) {
            return false;
        }

        $client = $DB->get_record('local_user_provisioning_clients', ['id' => $token->client_id]);
        $client_id = $client ? $client->client_id : null;

        $oauthtoken = [
            'expires' => $token->expires,
            'client_id' => $client_id,
            'scope' => $token->scope
        ];

        return $oauthtoken;
    }

    public function setAccessToken($access_token, $client_id, $user_id, $expires, $scope = null) {
        global $DB;

        // if it exists, update it.
        if ($token = $DB->get_record('local_user_provisioning_accesstoken', ['access_token' => $access_token])) {
            // Get the client id from the client table.
            $client = $DB->get_record('local_user_provisioning_clients', ['client_id' => $client_id]);
            $token->client_id = $client ? $client->id : null;
            $token->expires = $expires;
            $DB->update_record('local_user_provisioning_accesstoken', $token);
        } else {
            $client = $DB->get_record('local_user_provisioning_clients', ['client_id' => $client_id]);
            $token = new \StdClass();
            $token->client_id = $client ? $client->id : null;
            $token->expires = $expires;
            $token->scope = $scope;
            $token->access_token = $access_token;
            $DB->insert_record('local_user_provisioning_accesstoken', $token);
        }
        return true;
    }

    /* OAuth2\Storage\AuthorizationCodeInterface */
    public function getAuthorizationCode($code) {
        // Authorization codes are not supported in this implementation, so always return false.
        return false;
    }

    public function setAuthorizationCode($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null,
                                         $code_challenge = null, $code_challenge_method = null) {
        // Authorization codes are not supported in this implementation, so always return false.
        return false;
    }

    private function setAuthorizationCodeWithIdToken($code, $client_id, $user_id, $redirect_uri, $expires, $scope = null, $id_token = null) {
        // Authorization codes are not supported in this implementation, so always return false.
        return false;
    }

    public function expireAuthorizationCode($code) {
        // Authorization codes are not supported in this implementation, so always return false.
        return false;
    }

    /* OAuth2\Storage\UserCredentialsInterface */
    public function checkUserCredentials($username, $password) {
        // User login is not supported in this implementation, so always return false.
        return false;
    }

    public function getUserDetails($username) {
        // User login is not supported in this implementation, so always return false.
        return false;
    }

    /* UserClaimsInterface */
    public function getUserClaims($user_id, $claims) {
        // User login is not supported in this implementation, so always return false.
        return false;
    }

    protected function getUserClaim($claim, $userDetails) {
        // User login is not supported in this implementation, so always return false.
        return false;
    }

    /* OAuth2\Storage\RefreshTokenInterface */
    public function getRefreshToken($refresh_token) {
        // Refresh tokens are not supported in this implementation, so always return false.
        return false;
    }

    public function setRefreshToken($refresh_token, $client_id, $user_id, $expires, $scope = null) {
        // Refresh tokens are not supported in this implementation, so always return false.
        return false;
    }

    public function unsetRefreshToken($refresh_token) {
        // Refresh tokens are not supported in this implementation, so always return false.
        return false;
    }

    // plaintext passwords are bad!  Override this for your application
    protected function checkPassword($user, $password) {
        // Passwords are not supported in this implementation, so always return false.
        return false;
    }

    public function getUser($username) {
        // Passwords are not supported in this implementation, so always return false.
        return false;
    }

    public function setUser($username, $password, $firstName = null, $lastName = null) {
        // Passwords are not supported in this implementation, so always return false.
        return false;
    }

    /* ScopeInterface */
    public function scopeExists($scope) {
        // Queryable scopes are not supported in this implementation, so always return false.
        return false;
    }

    public function getDefaultScope($client_id = null) {
        // Queryable scopes are not supported in this implementation, so always return false.
        return null;
    }

    /* JWTBearerInterface */
    public function getClientKey($client_id, $subject) {
        // Queryable scopes are not supported in this implementation, so always return false.
        return null;
    }

    public function getClientScope($client_id) {
        // Queryable scopes are not supported in this implementation, so always return false.
        return null;
    }

    public function getJti($client_id, $subject, $audience, $expiration, $jti) {
        //TODO: Needs cassandra implementation.
        throw new \Exception('getJti() for the Moodle driver is currently unimplemented.');
    }

    public function setJti($client_id, $subject, $audience, $expiration, $jti) {
        //TODO: Needs cassandra implementation.
        throw new \Exception('setJti() for the Moodle driver is currently unimplemented.');
    }

    /* PublicKeyInterface */
    public function getPublicKey($client_id = null) {
        // PKI is not supported in this implementation, so always return false.
        return false;
    }

    public function getPrivateKey($client_id = null) {
        // PKI is not supported in this implementation, so always return false.
        return false;
    }

    public function getEncryptionAlgorithm($client_id = null) {
        // PKI is not supported in this implementation, so always return false.
        return false;
    }

    /**
     * DDL to create OAuth2 database and tables for PDO storage
     *
     * @see https://github.com/dsquier/oauth2-server-php-mysql
     */
    public function getBuildSql($notused = false) {
        return false;
    }
}
