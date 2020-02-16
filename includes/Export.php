<?php

namespace LicenseManagerForWooCommerce;

use FPDF;
use LicenseManagerForWooCommerce\Enums\LicenseSource;
use LicenseManagerForWooCommerce\Enums\LicenseStatus;
use LicenseManagerForWooCommerce\Models\Resources\License as LicenseResourceModel;
use LicenseManagerForWooCommerce\Repositories\Resources\License as LicenseResourceRepository;

defined('ABSPATH') || exit;

class Export
{
    /**
     * Export Constructor.
     */
    public function __construct()
    {
        add_action('lmfwc_export_license_keys_pdf', array($this, 'exportLicenseKeysPdf'), 10, 1);
        add_action('lmfwc_export_license_keys_csv', array($this, 'exportLicenseKeysCsv'), 10, 1);
    }

    /**
     * Creates a PDF of license keys by the given array of ID's.
     * 
     * @param array $licenseKeyIds
     */
    public function exportLicenseKeysPdf($licenseKeyIds)
    {
        $licenseKeys = array();

        foreach ($licenseKeyIds as $licenseKeyId) {
            /** @var LicenseResourceModel $license */
            $license = LicenseResourceRepository::instance()->find($licenseKeyId);

            if (!$license) {
                continue;
            }

            $licenseKeys[] = array(
                'id' => $license->getId(),
                'order_id' => $license->getOrderId(),
                'product_id' => $license->getProductId(),
                'license_key' => $license->getDecryptedLicenseKey()
            );
        }

        $header = array(
            'id'          => __('ID', 'license-manager-for-woocommerce'),
            'order_id'    => __('Order ID', 'license-manager-for-woocommerce'),
            'product_id'  => __('Product ID', 'license-manager-for-woocommerce'),
            'license_key' => __('License key', 'license-manager-for-woocommerce')
        );

        ob_clean();

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->AddFont('Roboto-Bold', '', 'Roboto-Bold.php');
        $pdf->AddFont('Roboto-Regular', '', 'Roboto-Regular.php');
        $pdf->AddFont('RobotoMono-Regular', '', 'RobotoMono-Regular.php');
        $pdf->SetFont('Roboto-Bold', '', 10);

        // Header
        $pdf->Image(LMFWC_IMG_URL . 'lmfwc_logo.jpg', 10, 10, -300);
        $pdf->Ln(25);

        // Table Header
        $pdf->SetDrawColor(200, 200, 200);

        foreach ($header as $columnName => $col) {
            $width = 40;

            if ($columnName == 'id') {
                $width = 12;
            }

            if ($columnName == 'order_id'
                || $columnName == 'product_id'
            ) {
                $width = 20;
            }

            if ($columnName == 'license_key') {
                $width = 0;
            }

            $pdf->Cell($width, 10, $col, 'B');
        }

        // Data
        $pdf->Ln();

        foreach ($licenseKeys as $row) {
            foreach ($row as $columnName => $col) {
                $pdf->SetFont('Roboto-Regular', '', 8);
                $width = 40;

                if ($columnName == 'id') {
                    $width = 12;
                }

                if ($columnName == 'order_id'
                    || $columnName == 'product_id'
                ) {
                    $width = 20;
                }

                if ($columnName == 'license_key') {
                    $pdf->SetFont('RobotoMono-Regular', '', 8);
                    $width = 0;
                }

                $pdf->Cell($width, 6, $col, 'B');
            }

            $pdf->Ln();
        }

        $pdf->Output(date('YmdHis') . '_license_keys_export.pdf', 'D');
    }

    /**
     * Creates a CSV of license keys by the given array of ID's.
     * 
     * @param array $licenseKeyIds
     */
    public function exportLicenseKeysCsv($licenseKeyIds)
    {
        $licenseKeys = array();

        foreach ($licenseKeyIds as $licenseKeyId) {
            /** @var LicenseResourceModel $license */
            $license = LicenseResourceRepository::instance()->find($licenseKeyId);

            if (!$license) {
                continue;
            }

            $licenseKeys[] = array(
                'order_id'    => $license->getOrderId(),
                'product_id'  => $license->getProductId(),
                'license_key' => $license->getDecryptedLicenseKey(),
                'created_at'  => $license->getCreatedAt(),
                'expires_at'  => $license->getExpiresAt(),
                'valid_for'   => $license->getValidFor(),
                'source'      => LicenseSource::getExportLabel($license->getSource()),
                'status'      => LicenseStatus::getExportLabel($license->getStatus())
            );
        }

        $filename = date('YmdHis') . '_license_keys_export.csv';

        // disable caching
        $now = gmdate("D, d M Y H:i:s");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");

        // force download  
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");

        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");

        ob_clean();
        ob_start();
        $df = fopen("php://output", 'w');
        fputcsv($df, array_keys(reset($licenseKeys)));

        foreach ($licenseKeys as $row) {
           fputcsv($df, $row);
        }

        fclose($df);
        ob_end_flush();

        exit();
    }
}
