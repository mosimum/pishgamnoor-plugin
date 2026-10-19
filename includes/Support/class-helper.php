<?php
namespace Pishgamnoor\Studio\Support;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Small shared helpers used across admin and frontend.
 */
final class Helper {

    /**
     * Render a plugin template. Themes can override it by creating:
     * wp-content/themes/{theme}/pishgamnoor-studio/{name}
     */
    public static function template( $name, $data = array(), $echo = true ) {
        $name = ltrim( str_replace( '..', '', $name ), '/' );
        $file = locate_template( array( 'pishgamnoor-studio/' . $name ) );

        if ( ! $file ) {
            $file = PISHGAMNOOR_STUDIO_PATH . 'templates/' . $name;
        }

        if ( ! is_readable( $file ) ) {
            return '';
        }

        if ( is_array( $data ) ) {
            extract( $data, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
        }

        ob_start();
        include $file;
        $html = ob_get_clean();

        if ( $echo ) {
            echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template output is escaped inside.
            return '';
        }

        return $html;
    }

    public static function get_meta( $post_id, $key, $default = '' ) {
        $value = get_post_meta( $post_id, $key, true );
        return ( '' === $value || null === $value ) ? $default : $value;
    }

    public static function get_json_meta( $post_id, $key, $default = array() ) {
        $raw = get_post_meta( $post_id, $key, true );
        if ( empty( $raw ) ) {
            return $default;
        }

        if ( is_array( $raw ) ) {
            return self::repair_json_tree( $raw );
        }

        $decoded = json_decode( (string) $raw, true );

        // A previously double-encoded value can decode to another JSON string.
        if ( is_string( $decoded ) ) {
            $decoded = json_decode( $decoded, true );
        }

        if ( is_array( $decoded ) ) {
            return self::repair_json_tree( $decoded );
        }

        // Last resort: the raw string may contain legacy "uXXXX" sequences.
        $repaired = self::repair_json_string( (string) $raw );
        if ( $repaired !== $raw ) {
            $decoded = json_decode( $repaired, true );
            if ( is_array( $decoded ) ) {
                return self::repair_json_tree( $decoded );
            }
        }

        return $default;
    }

    /**
     * Store JSON in post meta without WordPress stripping the Unicode escapes.
     *
     * update_metadata() runs wp_unslash() on the value. Using JSON_UNESCAPED_UNICODE
     * prevents \uXXXX sequences from becoming literal "uXXXX" text, and wp_slash()
     * keeps quotes/backslashes intact through the same unslash step.
     */
    public static function encode_json_meta( $data ) {
        $json = wp_json_encode( $data, JSON_UNESCAPED_UNICODE );

        return is_string( $json ) ? wp_slash( $json ) : '';
    }

    private static function repair_json_tree( $value ) {
        if ( is_array( $value ) ) {
            $out = array();
            foreach ( $value as $key => $item ) {
                $out[ $key ] = self::repair_json_tree( $item );
            }
            return $out;
        }

        return is_string( $value ) ? self::repair_json_string( $value ) : $value;
    }

    /**
     * Repair legacy values where update_post_meta() stripped the backslashes
     * from JSON \uXXXX escapes, leaving strings such as "u06afu0631...".
     */
    private static function repair_json_string( $value ) {
        if ( ! is_string( $value ) || '' === $value ) {
            return $value;
        }

        if ( ! preg_match_all( '/u([0-9a-fA-F]{4})/', $value, $matches ) ) {
            return $value;
        }

        // Avoid touching normal text that merely contains "u" + four hex chars.
        if ( count( $matches[0] ) < 2 ) {
            return $value;
        }

        $repaired = preg_replace_callback(
            '/u([0-9a-fA-F]{4})/',
            static function ( $match ) {
                $char = json_decode( '"\\u' . $match[1] . '"' );
                return is_string( $char ) ? $char : $match[0];
            },
            $value
        );

        return is_string( $repaired ) ? $repaired : $value;
    }

    public static function color( $value, $fallback = '#f7ce26' ) {
        $color = sanitize_hex_color( $value );
        return $color ? $color : $fallback;
    }

    public static function number( $value, $min, $max, $fallback ) {
        if ( ! is_numeric( $value ) ) {
            return $fallback;
        }
        return max( $min, min( $max, $value + 0 ) );
    }

    public static function bool( $value ) {
        return ( 1 === (int) $value || true === $value || '1' === (string) $value );
    }

    /**
     * CSS variables for the studio root.
     */
    public static function css_vars( $settings, $atts = array() ) {
        $accent  = self::color( $atts['accent'] ?? $settings['accent'], '#f7ce26' );
        $bg      = self::color( $atts['bg'] ?? $settings['bg'], '#06071a' );
        $panel   = self::color( $atts['panel'] ?? $settings['panel'], '#0b0d1b' );
        $panel_2 = self::color( $atts['panel_2'] ?? $settings['panel_2'], '#080a14' );
        $text    = self::color( $atts['text'] ?? $settings['text'], '#fffdf7' );
        $muted   = self::color( $atts['muted'] ?? $settings['muted'], '#a9a69e' );

        $radius   = self::number( $atts['radius'] ?? $settings['radius'], 0, 24, 12 );
        $font     = self::number( $atts['font_size'] ?? $settings['font_size'], 12, 22, 16 );
        $title    = self::number( $atts['title_size'] ?? $settings['title_size'], 18, 40, 24 );
        $small    = self::number( $atts['small_size'] ?? $settings['small_size'], 10, 18, 13 );
        $heading  = self::number( $atts['heading_size'] ?? $settings['heading_size'], 28, 72, 48 );
        $shell    = self::number( $atts['shell_height'] ?? $settings['shell_height'], 600, 1000, 820 );
        $stage    = self::number( $atts['stage_height'] ?? $settings['stage_height'], 400, 1000, 820 );
        $padding  = self::number( $atts['section_padding'] ?? $settings['section_padding'], 0, 120, 48 );
        $font_css = Settings::font_stack( $settings['font_family'] ?? 'IRANSansX' );

        return implode(
            ';',
            array(
                '--pn-accent:' . $accent,
                '--pn-bg:' . $bg,
                '--pn-panel:' . $panel,
                '--pn-panel-2:' . $panel_2,
                '--pn-text:' . $text,
                '--pn-muted:' . $muted,
                '--pn-radius:' . $radius . 'px',
                '--pn-font-size:' . $font . 'px',
                '--pn-title-size:' . $title . 'px',
                '--pn-small-size:' . $small . 'px',
                '--pn-heading-size:' . $heading . 'px',
                '--pn-shell-height:' . $shell . 'px',
                '--pn-stage-height:' . $stage . 'px',
                '--pn-section-padding:' . $padding . 'px',
                '--pn-font:' . $font_css,
            )
        );
    }
}
