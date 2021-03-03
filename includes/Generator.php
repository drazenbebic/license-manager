<?php

namespace LicenseManagerForWooCommerce;

use Exception;
use LicenseManagerForWooCommerce\Models\Resources\Generator as GeneratorResourceModel;

defined('ABSPATH') || exit;

class Generator
{
    /**
     * Generator Constructor.
     */
    public function __construct()
    {
        add_filter('lmfwc_generate_license_keys', array($this, 'generateLicenseKeys'), 10, 3);
    }

    /**
     * Generate a single license string
     *
     * @param string $charset     Character map from which the license will be generated
     * @param int    $chunks      Number of chunks
     * @param int    $chunkLength The length of an individual chunk
     * @param string $separator   Separator used
     * @param string $prefix      Prefix used
     * @param string $suffix      Suffix used
     *
     * @return string
     */
    private function generateLicenseString($charset, $chunks, $chunkLength, $separator, $prefix, $suffix)
    {
        $charsetLength = strlen($charset);
        $licenseString = $prefix;

        // loop through the chunks
        for ($i=0; $i < $chunks; $i++) {
            // add n random characters from $charset to chunk, where n = $chunkLength
            for ($j = 0; $j < $chunkLength; $j++) {
                $licenseString .= $charset[rand(0, $charsetLength - 1)];
            }
            // do not add the separator on the last iteration
            if ($i < $chunks - 1) {
                $licenseString .= $separator;
            }
        }

        $licenseString .= $suffix;

        return $licenseString;
    }

    /**
     * Bulk create license keys, if possible for given parameters.
     *
     * @param int $amount Number of license keys to be generated
     * @param GeneratorResourceModel $generator Generator used for the license keys
     * @param array $licenses Number of license keys to be generated
     *
     * @return array
     * @throws Exception
     */
    public function generateLicenseKeys($amount, $generator, $licenses = array())
    {
        // check if it's possible to create as many combinations using the input args
        $uniqueCharacters = count(array_unique(str_split($generator->getCharset())));
        $maxPossibleKeys = pow($uniqueCharacters, $generator->getChunks() * $generator->getChunkLength());

        if ($amount > $maxPossibleKeys) {
            throw new Exception('It\'s not possible to generate that many keys with the given parameters, there are not enough combinations. Please review your inputs.');
        }

        // Generate the license strings
        for ($i = 0; $i < $amount; $i++) {
            $licenses[] = $this->generateLicenseString(
                $generator->getCharset(),
                $generator->getChunks(),
                $generator->getChunkLength(),
                $generator->getSeparator(),
                $generator->getPrefix(),
                $generator->getSuffix()
            );
        }

        // Remove duplicate entries from the array
        $licenses = array_unique($licenses);

        // check if any licenses have been removed
        if (count($licenses) < $amount) {
            // regenerate removed license keys, repeat until there are no duplicates
            while (count($licenses) < $amount) {
                $licenses = $this->generateLicenseKeys(($amount - count($licenses)), $generator, $licenses);
            }
        }

        // Reindex and return the array
        return array_values($licenses);
    }
}