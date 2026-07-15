<?php
/**
 * Minimal PDF Writer (no library, no Composer, no external service)
 *
 * "Standalone" requirement ka matlab hai ki PDF export ke liye bhi
 * koi third-party library (TCPDF/mPDF/Dompdf) ya Composer vendor
 * folder nahi chahiye. Yeh class seedhe valid PDF 1.4 bytes khud
 * likhti hai — Catalog, Pages, ek base-14 Helvetica font, aur har
 * page ka apna content stream, sab hand-written PDF object syntax me.
 *
 * Sirf simple tabular reports ke liye bana hai (jo is plugin ko chahiye)
 * — koi images/embedded fonts nahi, isliye implementation chhota aur
 * predictable rehta hai.
 *
 * @package Premium_Analytics_Dashboard_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PAD_PDF_Writer
 */
class PAD_PDF_Writer {

	/**
	 * Page width in points (A4 landscape).
	 *
	 * @var int
	 */
	private $page_width = 842;

	/**
	 * Page height in points (A4 landscape).
	 *
	 * @var int
	 */
	private $page_height = 595;

	/**
	 * Margin in points.
	 *
	 * @var int
	 */
	private $margin = 36;

	/**
	 * Line height in points.
	 *
	 * @var int
	 */
	private $line_height = 14;

	/**
	 * Body font size.
	 *
	 * @var int
	 */
	private $font_size = 8;

	/**
	 * Title font size.
	 *
	 * @var int
	 */
	private $title_font_size = 14;

	/**
	 * PDF bytes generate karke seedha browser ko download deta hai
	 * (headers set karke, aur script terminate karke).
	 *
	 * @param string   $title      Report title (PDF ke top par).
	 * @param string[] $headers    Column headers.
	 * @param int[]    $col_widths Har column ki width (points me), sum <= usable width honi chahiye.
	 * @param array    $rows       Har row ek string[] (headers jitni columns).
	 * @param string   $filename   Download filename.
	 * @return void
	 */
	public function download( $title, $headers, $col_widths, $rows, $filename ) {

		$pdf_bytes = $this->build( $title, $headers, $col_widths, $rows );

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		header( 'Content-Length: ' . strlen( $pdf_bytes ) );

		echo $pdf_bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw binary PDF bytes, not HTML.
		exit;
	}

	/**
	 * Poori PDF file ke raw bytes banata hai.
	 *
	 * @param string   $title      Report title.
	 * @param string[] $headers    Column headers.
	 * @param int[]    $col_widths Column widths.
	 * @param array    $rows       Table rows.
	 * @return string
	 */
	private function build( $title, $headers, $col_widths, $rows ) {

		$usable_height = $this->page_height - ( 2 * $this->margin ) - 40;
		$rows_per_page = max( 1, (int) floor( $usable_height / $this->line_height ) );
		$chunks        = array_chunk( $rows, $rows_per_page );

		if ( empty( $chunks ) ) {
			$chunks = array( array() );
		}

		$page_count = count( $chunks );

		$objects = array();

		$kids = array();
		for ( $k = 0; $k < $page_count; $k++ ) {
			$kids[] = ( 4 + ( 2 * $k ) ) . ' 0 R';
		}

		$objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
		$objects[2] = '<< /Type /Pages /Kids [' . implode( ' ', $kids ) . '] /Count ' . $page_count . ' >>';
		$objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

		for ( $k = 0; $k < $page_count; $k++ ) {

			$page_id    = 4 + ( 2 * $k );
			$content_id = 5 + ( 2 * $k );

			$stream = $this->build_page_stream( $title, $headers, $col_widths, $chunks[ $k ], 0 === $k );

			$objects[ $page_id ]    = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . $this->page_width . ' ' . $this->page_height . '] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $content_id . ' 0 R >>';
			$objects[ $content_id ] = array( 'stream' => $stream );
		}

		return $this->serialize( $objects );
	}

	/**
	 * Ek page ka content stream (text positioning commands) banata hai.
	 *
	 * @param string   $title       Report title.
	 * @param string[] $headers     Column headers.
	 * @param int[]    $col_widths  Column widths.
	 * @param array    $rows        Is page ke rows.
	 * @param bool     $is_first_page Kya yeh pehla page hai (title dikhega).
	 * @return string
	 */
	private function build_page_stream( $title, $headers, $col_widths, $rows, $is_first_page ) {

		$parts = array( 'BT', "/F1 {$this->font_size} Tf" );
		$y     = $this->page_height - $this->margin;

		if ( $is_first_page ) {
			$parts[] = "/F1 {$this->title_font_size} Tf";
			$parts[] = "1 0 0 1 {$this->margin} {$y} Tm";
			$parts[] = '(' . $this->escape( $title ) . ') Tj';
			$parts[] = "/F1 {$this->font_size} Tf";
			$y      -= 26;
		}

		$x = $this->margin;

		foreach ( $headers as $index => $header ) {
			$parts[] = "1 0 0 1 {$x} {$y} Tm";
			$parts[] = '(' . $this->escape( strtoupper( $header ) ) . ') Tj';
			$x      += isset( $col_widths[ $index ] ) ? $col_widths[ $index ] : 80;
		}

		$y -= $this->line_height;

		if ( empty( $rows ) ) {
			$parts[] = "1 0 0 1 {$this->margin} {$y} Tm";
			$parts[] = '(' . $this->escape( __( 'Koi lead is filter ke saath nahi mila.', 'premium-analytics-dashboard-pro' ) ) . ') Tj';
		}

		foreach ( $rows as $row ) {

			$x = $this->margin;

			foreach ( $row as $index => $cell ) {
				$width     = isset( $col_widths[ $index ] ) ? $col_widths[ $index ] : 80;
				$max_chars = max( 4, (int) floor( $width / ( $this->font_size * 0.55 ) ) );
				$text      = $this->truncate( (string) $cell, $max_chars );

				$parts[] = "1 0 0 1 {$x} {$y} Tm";
				$parts[] = '(' . $this->escape( $text ) . ') Tj';
				$x      += $width;
			}

			$y -= $this->line_height;
		}

		$parts[] = 'ET';

		return implode( "\n", $parts );
	}

	/**
	 * Text ko PDF string-literal ke liye safe banata hai — single-byte
	 * encoding me convert karta hai aur `( ) \` escape karta hai.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private function escape( $text ) {

		$text      = (string) $text;
		$converted = @mb_convert_encoding( $text, 'ISO-8859-1', 'UTF-8' );

		if ( false === $converted || null === $converted ) {
			$converted = preg_replace( '/[^\x20-\x7E]/', '?', $text );
		}

		return str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $converted );
	}

	/**
	 * Column width me fit karne ke liye text ko truncate karta hai.
	 *
	 * @param string $text      Raw text.
	 * @param int    $max_chars Max characters allowed.
	 * @return string
	 */
	private function truncate( $text, $max_chars ) {

		if ( strlen( $text ) <= $max_chars ) {
			return $text;
		}

		return substr( $text, 0, max( 1, $max_chars - 1 ) ) . '…';
	}

	/**
	 * PDF objects array ko final valid PDF bytes (header, objects,
	 * xref table, trailer) me serialize karta hai.
	 *
	 * @param array $objects Object number => body string, ya array('stream'=>...) for streams.
	 * @return string
	 */
	private function serialize( $objects ) {

		$out     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
		$offsets = array();
		$total   = count( $objects );

		for ( $i = 1; $i <= $total; $i++ ) {

			$offsets[ $i ] = strlen( $out );
			$body          = $objects[ $i ];

			if ( is_array( $body ) && isset( $body['stream'] ) ) {
				$out .= "{$i} 0 obj\n<< /Length " . strlen( $body['stream'] ) . " >>\nstream\n" . $body['stream'] . "\nendstream\nendobj\n";
			} else {
				$out .= "{$i} 0 obj\n{$body}\nendobj\n";
			}
		}

		$xref_offset = strlen( $out );

		$out .= "xref\n0 " . ( $total + 1 ) . "\n0000000000 65535 f \n";

		for ( $i = 1; $i <= $total; $i++ ) {
			$out .= sprintf( "%010d 00000 n \n", $offsets[ $i ] );
		}

		$out .= "trailer\n<< /Size " . ( $total + 1 ) . " /Root 1 0 R >>\nstartxref\n{$xref_offset}\n%%EOF";

		return $out;
	}
}
