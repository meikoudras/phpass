<?php
/**
 * PHP Password Library
 *
 * @package PHPassLib\Hashes
 * @author Ryan Chouinard <rchouinard@gmail.com>
 * @copyright Copyright (c) 2012, Ryan Chouinard
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 * @version 3.0.0-dev
 */

namespace PHPassLib\Hash;
use PHPassLib\Hash,
    PHPassLib\Utilities,
    PHPassLib\Exception\InvalidArgumentException;

/**
 * MD5 Crypt Module
 *
 * MD5 crypt is a cryptographic hash function which uses an algorithm based on
 * MD5 in combination with a salt value. The resulting function is more
 * computationally expensive, and therefore slower to calculate, than straigh
 * MD5. the slower speed helps to discourage brute-force attacks while the salt
 * value defeats rainbow tables.
 *
 * This method uses a fixed number of rounds and is no longer as
 * computationally expensive as it once was. It is not recommended to use
 * MD5 crypt for new projects. consider using BCrypt or PBKDF2-SHA512 instead.
 *
 * <code>
 * <?php
 * use PHPassLib\Hash\MD5Crypt;
 *
 * $hash = MD5Crypt::hash($password);
 * if (MD5Crypt::verify($password, $hash)) {
 *     // Password matches, user is authenticated
 * }
 * </code>
 *
 * @link http://www.freebsd.org/cgi/cvsweb.cgi/~checkout~/src/lib/libcrypt/crypt.c?rev=1.2
 *     Poul-Henning Kamp's authoritative FreeBSD implementation
 * @link http://en.wikipedia.org/wiki/Crypt_(Unix)#MD5-based_scheme MD5 crypt
 *     implementation in UNIX crypt() at Wikipedia
 */
class MD5Crypt implements Hash
{

    /**
     * Generate a config string suitable for use with md5crypt hashes.
     *
     * Available options:
     *  - salt: Salt string which must be between 0 and 8 characters
     *      in length, using characters in the range ./0-9A-Za-z. If none is
     *      given, a valid salt value will be generated.
     *
     * @param array $config Array of configuration options.
     * @return string Configuration string in the format
     *     "$1$<salt>$".
     * @throws InvalidArgumentException Throws an InvalidArgumentException if
     *     any passed-in configuration options are invalid.
     */
    public static function genConfig(Array $config = array ())
    {
        $defaults = array (
            'salt' => static::genSalt(),
        );
        $config = array_merge($defaults, array_change_key_case($config, CASE_LOWER));

        if (static::validateOptions($config)) {
            return sprintf('$1$%s$', $config['salt']);
        } else {
            return '*1';
        }
    }

    /**
     * Generate a hash using a pre-defined config string.
     *
     * @param string $password Password string.
     * @param string $config Configuration string.
     * @return string Returns the hash string on success. On failure, one of
     *     *0 or *1 is returned.
     */
    public static function genHash($password, $config)
    {
        $hash = crypt($password, $config);
        if (!preg_match('/^\$1\$[\.\/0-9A-Za-z]{0,8}\$[\.\/0-9A-Za-z]{22}$/', $hash)) {
            $hash = ($config == '*0') ? '*1' : '*0';
        }

        return $hash;
    }

    /**
     * Generate a hash using either a pre-defined config string or an array.
     *
     * @param string $password Password string.
     * @param string|array $config Optional config string or array of options.
     * @return string Encoded password hash.
     */
    public static function hash($password, $config = array ())
    {
        if (is_array($config)) {
            $config = static::genConfig($config);
        }

        return static::genHash($password, $config);
    }

    /**
     * Verify a password against a hash string.
     *
     * @param string $password Password string.
     * @param string $hash Hash string.
     * @return boolean Returns true if the password matches, false otherwise.
     */
    public static function verify($password, $hash)
    {
        return ($hash === static::hash($password, $hash));
    }

    /**
     * Generate a valid salt string.
     *
     * @param string $input Optional random string of raw bytes.
     * @return string Encoded salt string.
     */
    protected static function genSalt($input = null)
    {
        if (!$input) {
            $input = Utilities::genRandomBytes(6);
        }

        return Utilities::encode64($input, 6);
    }

    /**
     * Validate a set of module options.
     *
     * @param array $options Associative array of options.
     * @return boolean Returns true if all options are valid.
     * @throws InvalidArgumentException Throws an InvalidArgumentException
     *     if an invalid option value is encountered.
     */
    protected static function validateOptions(Array $options)
    {
        $options = array_change_key_case($options, CASE_LOWER);
        foreach ($options as $option => $value) switch ($option) {

            // Salt must be between 0 and 8 characters in length (inclusive)
            // and contain only characters in the range [./0-9A-Za-z].
            case 'salt':
                if (!preg_match('/^[\.\/0-9A-Za-z]{0,8}$/', $value)) {
                    throw new InvalidArgumentException("Salt parameter must be a string 0-8 characters in length containing only characters in the range ./0-9A-Za-z.");
                }
                break;

            default:
                break;

        }

        return true;
    }

}