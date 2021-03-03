<?php
/**
 * LicenseManager for WooCommerce - Generator functions
 *
 * Functions for generator manipulation.
 */

use LicenseManagerForWooCommerce\Models\Resources\Generator as GeneratorResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\Generator as GeneratorResourceRepository;

defined('ABSPATH') || exit;

/**
 * @param string      $name
 * @param string      $characterMap
 * @param string      $chunks
 * @param string      $chunkLength
 * @param null|string $separator
 * @param null|string $prefix
 * @param null|string $suffix
 * @param null|int    $expiresIn
 * @param null|int    $timesActivatedMax
 * @return GeneratorResourceModel|WP_Error
 */
function lmfwc_add_generator($name, $characterMap, $chunks, $chunkLength, $separator = null, $prefix = null, $suffix = null, $expiresIn = null, $timesActivatedMax = null) {
    $name         = sanitize_text_field($name);
    $characterMap = sanitize_text_field($characterMap);
    $chunks       = (int)$chunks;
    $chunkLength  = (int)$chunkLength;

    if (strlen($name) > 255) {
        return new WP_Error('The generator\'s name cannot exceed 255 characters.');
    }

    if (strlen($characterMap) > 255) {
        return new WP_Error('The generator\'s character map cannot exceed 255 characters.');
    }

    if ($chunks > 4294967295) {
        return new WP_Error('The generator\'s number of chunks cannot be larger than 4294967295.');
    }

    if ($chunkLength > 4294967295) {
        return new WP_Error('The generator\'s chunk length cannot be larger than 4294967295.');
    }

    if ($separator !== null) {
        $separator = (string)$separator;

        if (strlen($separator) > 255) {
            return new WP_Error('The generator\'s separator cannot cannot exceed 255 characters.');
        }
    }

    if ($prefix !== null) {
        $prefix = (string)$prefix;

        if (strlen($prefix) > 255) {
            return new WP_Error('The generator\'s prefix cannot cannot exceed 255 characters.');
        }
    }

    if ($suffix !== null) {
        $suffix = (string)$suffix;

        if (strlen($suffix) > 255) {
            return new WP_Error('The generator\'s suffix cannot cannot exceed 255 characters.');
        }
    }

    if ($expiresIn !== null) {
        $expiresIn = (int)$expiresIn;

        if ($expiresIn > 4294967295) {
            return new WP_Error('The generator\'s expires_in cannot be larger than 4294967295.');
        }
    }

    if ($timesActivatedMax !== null) {
        $timesActivatedMax = (int)$timesActivatedMax;

        if ($timesActivatedMax > 4294967295) {
            return new WP_Error('The generator\'s timesActivatedMax cannot be larger than 4294967295.');
        }
    }

    /** @var GeneratorResourceModel $generator */
    $generator = GeneratorResourceRepository::instance()->insert(
        array(
            'name'                => $name,
            'charset'             => $characterMap,
            'chunks'              => $chunks,
            'chunk_length'        => $chunkLength,
            'times_activated_max' => $timesActivatedMax,
            'separator'           => $separator,
            'prefix'              => $prefix,
            'suffix'              => $suffix,
            'expires_in'          => $expiresIn
        )
    );

    if (!$generator) {
        return new WP_Error('The generator could not be created.');
    }

    return $generator;
}

/**
 * Returns a single generator from the database.
 *
 * @param int $generatorId
 * @return GeneratorResourceModel|WP_Error
 */
function lmfwc_get_generator($generatorId)
{
    /** @var GeneratorResourceModel $generator */
    $generator = GeneratorResourceRepository::instance()->find((int)$generatorId);

    if (!$generator) {
        return new WP_Error('The generator could not be found.');
    }

    return $generator;
}

/**
 * Retrieves multiple generators by a query array.
 *
 * @param array $query Key/value pairs with the generator table column names as keys
 * @return GeneratorResourceModel[]
 */
function lmfwc_get_generators($query)
{
    /** @var GeneratorResourceModel[] $generators */
    $generators = GeneratorResourceRepository::instance()->findAllBy($query);

    if (!$generators) {
        return array();
    }

    return $generators;
}

/**
 * Updates a generator.
 *
 * @param int   $generatorId   Generator ID
 * @param array $generatorData Key/value pairs with the generator table column names as keys
 * @return GeneratorResourceModel|WP_Error
 */
function lmfwc_update_generator($generatorId, $generatorData)
{
    $updateData = array();

    /** @var GeneratorResourceModel $oldGenerator */
    $oldGenerator = GeneratorResourceRepository::instance()->find((int)$generatorId);

    if (!$oldGenerator) {
        return new WP_Error('The generator could not be found.');
    }

    // Name
    if (isset($generatorData['name'])) {
        $name = sanitize_text_field($generatorData['name']);

        if (strlen($name) > 255) {
            return new WP_Error('The generator\'s name cannot exceed 255 characters.');
        }

        $updateData['name'] = $name;
    }

    // Character map
    if (isset($generatorData['charset'])) {
        $charset = sanitize_text_field($generatorData['charset']);

        if (strlen($charset) > 255) {
            return new WP_Error('The generator\'s character map cannot exceed 255 characters.');
        }

        $updateData['charset'] = $charset;
    }

    // Chunks
    if (isset($generatorData['chunks'])) {
        $chunks = (int)$generatorData['chunks'];

        if ($chunks > 4294967295) {
            return new WP_Error('The generator\'s chunks cannot be larger than 4294967295.');
        }

        $updateData['chunks'] = $chunks;
    }

    // Chunk length
    if (isset($generatorData['chunk_length'])) {
        $expiresIn = (int)$generatorData['chunk_length'];

        if ($expiresIn > 4294967295) {
            return new WP_Error('The generator\'s chunk_length cannot be larger than 4294967295.');
        }

        $updateData['chunk_length'] = $expiresIn;
    }

    // Times activated max
    if (isset($generatorData['times_activated_max'])) {
        $expiresIn = (int)$generatorData['times_activated_max'];

        if ($expiresIn > 4294967295) {
            return new WP_Error('The generator\'s times_activated_max cannot be larger than 4294967295.');
        }

        $updateData['times_activated_max'] = $expiresIn;
    }

    // Separator
    if (isset($generatorData['separator'])) {
        $separator = sanitize_text_field($generatorData['separator']);

        if (strlen($separator) > 255) {
            return new WP_Error('The generator\'s separator cannot exceed 255 characters.');
        }

        $updateData['separator'] = $separator;
    }

    // Prefix
    if (isset($generatorData['prefix'])) {
        $prefix = sanitize_text_field($generatorData['prefix']);

        if (strlen($prefix) > 255) {
            return new WP_Error('The generator\'s prefix cannot exceed 255 characters.');
        }

        $updateData['prefix'] = $prefix;
    }

    // Suffix
    if (isset($generatorData['suffix'])) {
        $suffix = sanitize_text_field($generatorData['suffix']);

        if (strlen($suffix) > 255) {
            return new WP_Error('The generator\'s suffix cannot exceed 255 characters.');
        }

        $updateData['suffix'] = $suffix;
    }

    // Expires in
    if (isset($generatorData['expires_in'])) {
        $expiresIn = (int)$generatorData['expires_in'];

        if ($expiresIn > 4294967295) {
            return new WP_Error('The generator\'s expires_in cannot be larger than 4294967295.');
        }

        $updateData['expires_in'] = $expiresIn;
    }

    /** @var GeneratorResourceModel $generator */
    $generator = GeneratorResourceRepository::instance()->update($generatorId, $updateData);

    if (!$generator) {
        return new WP_Error('The generator could not be created.');
    }

    return $generator;
}

/**
 * Deletes generators from the database.
 *
 * @param int|int[] $generatorId A single generator ID, or an array of generator IDs
 * @return bool|WP_Error
 */
function lmfwc_delete_generator($generatorId)
{
    if (!is_array($generatorId)) {
        $generatorId = (array)$generatorId;
    }

    /** @var GeneratorResourceModel $generator */
    $generator = GeneratorResourceRepository::instance()->delete($generatorId);

    if (!$generator) {
        return new WP_Error('The generator(s) could not be deleted.');
    }

    return true;
}

/**
 * Use a generator to create license keys.
 *
 * @param int $generatorId
 * @param int $amount
 * @return string[]|WP_Error
 */
function lmfwc_use_generator($generatorId, $amount)
{
    /** @var GeneratorResourceModel $generator */
    $generator = GeneratorResourceRepository::instance()->find((int)$generatorId);

    if (!$generator) {
        return new WP_Error('The generator could not be found.');
    }

    return apply_filters('lmfwc_generate_license_keys', (int)$amount, $generator);
}
