<?php
/**
 * PHP Password Library
 *
 * @package PHPass\Hashes
 * @category Cryptography
 * @author Ryan Chouinard <rchouinard at gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @link https://github.com/rchouinard/phpass Project at GitHub
 */

/**
 * @namespace
 */
namespace Phpass\Hash\Adapter;
use Phpass\Exception\InvalidArgumentException,
    Phpass\Exception\RuntimeException;

/**
 * SHA-1 crypt hash adapter
 *
 * @package PHPass\Hashes
 * @category Cryptography
 * @author Ryan Chouinard <rchouinard at gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.html MIT License
 * @link https://github.com/rchouinard/phpass Project at GitHub
 * @since 2.1.0
 */
class Sha1Crypt extends Base
{

    /**
     * Number of rounds used to generate new hashes.
     *
     * @var integer
     */
    protected $_iterationCount = 40000;

    /**
     * Minimum allowed value for the iteration count.
     *
     * @var integer
     */
    protected $_iterationCountMin = 1;

    /**
     * Maximum allowed value for the iteration count.
     *
     * @var integer
     */
    protected $_iterationCountMax = 4294967295;

    /**
     * Return a hashed string.
     *
     * @param string $password
     *   The string to be hashed.
     * @param string $salt
     *   An optional salt string to base the hashing on. If not provided, a
     *   suitable string is generated by the adapter.
     * @return string
     *   Returns the hashed string. On failure, a standard crypt error string
     *   is returned which is guaranteed to differ from the salt.
     * @throws RuntimeException
     *   A RuntimeException is thrown on failure if
     *   self::$_throwExceptionOnFailure is true.
     */
    public function crypt($password, $salt = null)
    {
        if (!$salt) {
            $salt = $this->genSalt();
        }

        $hash = '*0';
        if ($this->verify($salt)) {
            $parts = $this->_getSettings($salt);
            $rounds = $parts['rounds'];

            $checksum = hash_hmac('sha1', $parts['salt'] . '$sha1$' . $parts['rounds'], $password, true);
            --$rounds;
            if ($rounds) {
                do {
                    $checksum = hash_hmac('sha1', $checksum, $password, true);
                } while (--$rounds);
            }

            // Shuffle the bits around a bit
            $tmp = '';
            foreach (array (2, 1, 0, 5, 4, 3, 8, 7, 6, 11, 10, 9, 14, 13, 12, 17, 16, 15, 0, 19, 18) as $offset) {
                $tmp .= $checksum[$offset];
            }
            $checksum = $tmp;

            $hash = '$sha1$' . $parts['rounds'] . '$' . $parts['salt'] . '$' . $this->_encode64($checksum, 21);
        }

        if (!$this->verifyHash($hash)) {
            $hash = ($salt != '*0') ? '*0' : '*1';
            if ($this->_throwExceptionOnFailure) {
                throw new RuntimeException('Failed generating a valid hash', $hash);
            }
        }

        return $hash;
    }

    /**
     * Generate a salt string compatible with this adapter.
     *
     * @param string $input
     *   Optional random 48-bit string to use when generating the salt.
     * @return string
     *   Returns the generated salt string.
     */
    public function genSalt($input = null)
    {
        if (!$input) {
            $input = $this->_getRandomBytes(6);
        }

        $identifier = 'sha1';
        $salt = $this->_encode64($input, 6);

        return '$' . $identifier . '$' . $this->_iterationCount . '$' . $salt . '$';
    }

    /**
     * Set adapter options.
     *
     * Expects an associative array of option keys and values used to configure
     * the hash adapter instance.
     *
     * <dl>
     *   <dt>iterationCount</dt>
     *     <dd>An integer value between 1 and 4294967295, inclusive. This
     *     value determines the cost factor associated with generating a new
     *     hash value. A higher number means a higher cost. Defaults to
     *     40000.</dd>
     * </dl>
     *
     * @param Array $options
     *   Associative array of adapter options.
     * @return Bcrypt
     * @see Base::setOptions()
     */
    public function setOptions(Array $options)
    {
        parent::setOptions($options);

        $options = array_change_key_case($options, CASE_LOWER);
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'iterationcountlog2':
                    $value = (int) $value;
                    $value = bcpow(2, $value, 0);
                    // Fall through
                case 'iterationcount':
                    $value = (float) $value;
                    if (!ctype_digit((string) $value) || $value < $this->_iterationCountMin || $value > $this->_iterationCountMax) {
                        throw new InvalidArgumentException("Iteration count must be an integer between {$this->_iterationCountMin} and {$this->_iterationCountMax}");
                    }
                    $this->_iterationCount = $value;
                    break;
                default:
                    break;
            }
        }

        return $this;
    }

    /**
     * Check if a hash string is valid for the current adapter.
     *
     * @since 2.1.0
     * @param string $input
     *   Hash string to verify.
     * @return boolean
     *   Returns true if the input string is a valid hash value, false
     *   otherwise.
     */
    public function verifyHash($input)
    {
        return (1 === preg_match('/\$sha1\$\d+\$[\.\/0-9A-Za-z]{0,64}\$[\.\/0-9A-Za-z]{28}$/', $input));
    }

    /**
     * Check if a salt string is valid for the current adapter.
     *
     * @since 2.1.0
     * @param string $input
     *   Salt string to verify.
     * @return boolean
     *   Returns true if the input string is a valid salt value, false
     *   otherwise.
     */
    public function verifySalt($input)
    {
        return (1 === preg_match('/^\$sha1\$\d+\$[\.\/0-9A-Za-z]{0,64}\$?/', $input));
    }

    /**
     * Return an array of hash settings from a given salt string.
     *
     * @param unknown_type $input
     */
    protected function _getSettings($input)
    {
        $parts = array ();
        $matches = array ();
        if (1 === preg_match('/^\$sha1\$(\d+)\$([\.\/0-9A-Za-z]{0,64})(?:\$([\.\/0-9A-Za-z]{28}))?$/', rtrim($input, '$'), $matches)) {
            $parts['rounds'] = $matches[1];
            $parts['salt'] = $matches[2];
            $parts['checksum'] = $matches[3] ?: null;
        }
        return $parts;
    }

}