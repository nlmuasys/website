<?php

function wpjellyExtractArchive( $source_file, $target_dir ) {

    if ( empty( $source_file ) || empty( $target_dir ) ) {
        // Empty source or destination
        return false;
    }

    if ( !file_exists( $source_file ) ) {
        // File doesn't exist
        return false;
    }

    if ( file_exists( $target_dir ) && ( ! is_dir( $target_dir ) ) ) {
        // Target directory exists as a file, not a directory
        return false;
    }

    if ( !file_exists( $target_dir ) ) {
        if ( !mkdir( $target_dir ) ) {
            // Directory not found, and unable to create it
            return false;
        }
    }

    if ( class_exists( "ZipArchive" ) ) {
        // Extract using ZipArchive
        $lib = new ZipArchive;

        if ( !$lib->open( $source_file ) ) {
            return false;
        }

        if ( !$lib->extractTo( $target_dir ) ) {
            return false;
        }

        $lib->close();
    } else {
        // Extarct using PclZip
        require_once WP_JELLY_DIR . "core/lib/pclzip/pclzip.lib.php";

        $lib = new PclZip( $source_file );

        if ( $lib->extract( PCLZIP_OPT_PATH, $target_dir ) <= 0 ) {
            return false;
        }
    }

    return true;
}
