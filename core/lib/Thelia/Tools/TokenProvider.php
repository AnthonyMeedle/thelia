<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace Thelia\Tools;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Thelia\Core\Security\Exception\TokenAuthenticationException;

/**
 * Class TokenProvider
 * @package Thelia\Tools
 * @author Benjamin Perche <bperche@openstudio.fr>
 */
class TokenProvider
{
    /**
     * @var string The stored token for this page
     */
    protected $token;

    /**
     * @var string The token of the previous page
     */
    protected $previousToken;

    /**
     * @var SessionInterface The session where the token is stored
     */
    protected $session;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var TranslatorInterface The translator
     */
    protected $translator;

    /**
     * @var string the current name of the token
     */
    protected $tokenName;

    /**
     * @param SessionInterface    $session
     * @param TranslatorInterface $translator
     * @param $tokenName
     */
    public function __construct(Request $request, TranslatorInterface $translator, $tokenName)
    {
        /**
         * Store the services
         */
        $this->request = $request;
        $this->session = $request->getSession();
        $this->translator = $translator;
        $this->tokenName = $tokenName;
    }

    protected function saveToken()
    {
        if (null !== $sessionToken = $this->session->get($this->tokenName)) {
            $this->previousToken = $sessionToken;
            $this->session->set($this->tokenName, null);

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function assignToken()
    {
        if (null === $this->token) {
            $this->token = $this->getToken();
            $this->saveToken();

            $this->session->set($this->tokenName, $this->token);
        }

        return $this->token;
    }

    /**
     * @param $entryValue
     * @return bool
     * @throws \Thelia\Core\Security\Exception\TokenAuthenticationException
     */
    public function checkToken($entryValue)
    {
        $isValid = false;

        if (!$this->saveToken()) {
            throw new TokenAuthenticationException(
                "Tried to check a token without assigning it before"
            );
        } else {
            if ($this->previousToken !== $entryValue) {
                throw new TokenAuthenticationException(
                    "Tried to validate an invalid token"
                );
            } else {
                $isValid = true;
            }

            $this->session->set($this->tokenName, null);
        }

        return $isValid;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        $raw = $this->getOpenSSLRandom();

        if (false === $raw) {
            $raw = $this->getComplexRandom();
        }

        return md5($raw);
    }

    /**
     * @return string
     */
    protected function getOpenSSLRandom($length = 40)
    {
        if (!function_exists("openssl_random_pseudo_bytes")) {
            return false;
        }

        return openssl_random_pseudo_bytes($length);
    }

    /**
     * @return string
     */
    protected function getComplexRandom()
    {
        $firstValue = (float) (mt_rand(1, 0xFFFF) * rand(1, 0x10001));
        $secondValues = (float) (rand(1, 0xFFFF) * mt_rand(1, 0x10001));

        return microtime() . ceil($firstValue / $secondValues) . uniqid();
    }
}
