<?php
/**
 * Theme functions and definitions
 *
 * @package HelloElementor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HELLO_ELEMENTOR_VERSION', '3.1.1' );

if ( ! isset( $content_width ) ) {
	$content_width = 800; // Pixels.
}

if ( ! function_exists( 'hello_elementor_setup' ) ) {
	/**
	 * Set up theme support.
	 *
	 * @return void
	 */
	function hello_elementor_setup() {
		if ( is_admin() ) {
			hello_maybe_update_theme_version_in_db();
		}

		if ( apply_filters( 'hello_elementor_register_menus', true ) ) {
			register_nav_menus( [ 'menu-1' => esc_html__( 'Header', 'hello-elementor' ) ] );
			register_nav_menus( [ 'menu-2' => esc_html__( 'Footer', 'hello-elementor' ) ] );
		}

		if ( apply_filters( 'hello_elementor_post_type_support', true ) ) {
			add_post_type_support( 'page', 'excerpt' );
		}

		if ( apply_filters( 'hello_elementor_add_theme_support', true ) ) {
			add_theme_support( 'post-thumbnails' );
			add_theme_support( 'automatic-feed-links' );
			add_theme_support( 'title-tag' );
			add_theme_support(
				'html5',
				[
					'search-form',
					'comment-form',
					'comment-list',
					'gallery',
					'caption',
					'script',
					'style',
				]
			);
			add_theme_support(
				'custom-logo',
				[
					'height'      => 100,
					'width'       => 350,
					'flex-height' => true,
					'flex-width'  => true,
				]
			);

			/*
			 * Editor Style.
			 */
			add_editor_style( 'classic-editor.css' );

			/*
			 * Gutenberg wide images.
			 */
			add_theme_support( 'align-wide' );

			/*
			 * WooCommerce.
			 */
			if ( apply_filters( 'hello_elementor_add_woocommerce_support', true ) ) {
				// WooCommerce in general.
				add_theme_support( 'woocommerce' );
				// Enabling WooCommerce product gallery features (are off by default since WC 3.0.0).
				// zoom.
				add_theme_support( 'wc-product-gallery-zoom' );
				// lightbox.
				add_theme_support( 'wc-product-gallery-lightbox' );
				// swipe.
				add_theme_support( 'wc-product-gallery-slider' );
			}
		}
	}
}
add_action( 'after_setup_theme', 'hello_elementor_setup' );

function hello_maybe_update_theme_version_in_db() {
	$theme_version_option_name = 'hello_theme_version';
	// The theme version saved in the database.
	$hello_theme_db_version = get_option( $theme_version_option_name );

	// If the 'hello_theme_version' option does not exist in the DB, or the version needs to be updated, do the update.
	if ( ! $hello_theme_db_version || version_compare( $hello_theme_db_version, HELLO_ELEMENTOR_VERSION, '<' ) ) {
		update_option( $theme_version_option_name, HELLO_ELEMENTOR_VERSION );
	}
}

if ( ! function_exists( 'hello_elementor_display_header_footer' ) ) {
	/**
	 * Check whether to display header footer.
	 *
	 * @return bool
	 */
	function hello_elementor_display_header_footer() {
		$hello_elementor_header_footer = true;

		return apply_filters( 'hello_elementor_header_footer', $hello_elementor_header_footer );
	}
}

if ( ! function_exists( 'hello_elementor_scripts_styles' ) ) {
	/**
	 * Theme Scripts & Styles.
	 *
	 * @return void
	 */
	function hello_elementor_scripts_styles() {
		$min_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		if ( apply_filters( 'hello_elementor_enqueue_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor',
				get_template_directory_uri() . '/style' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( apply_filters( 'hello_elementor_enqueue_theme_style', true ) ) {
			wp_enqueue_style(
				'hello-elementor-theme-style',
				get_template_directory_uri() . '/theme' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}

		if ( hello_elementor_display_header_footer() ) {
			wp_enqueue_style(
				'hello-elementor-header-footer',
				get_template_directory_uri() . '/header-footer' . $min_suffix . '.css',
				[],
				HELLO_ELEMENTOR_VERSION
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'hello_elementor_scripts_styles' );

if ( ! function_exists( 'hello_elementor_register_elementor_locations' ) ) {
	/**
	 * Register Elementor Locations.
	 *
	 * @param ElementorPro\Modules\ThemeBuilder\Classes\Locations_Manager $elementor_theme_manager theme manager.
	 *
	 * @return void
	 */
	function hello_elementor_register_elementor_locations( $elementor_theme_manager ) {
		if ( apply_filters( 'hello_elementor_register_elementor_locations', true ) ) {
			$elementor_theme_manager->register_all_core_location();
		}
	}
}
add_action( 'elementor/theme/register_locations', 'hello_elementor_register_elementor_locations' );

if ( ! function_exists( 'hello_elementor_content_width' ) ) {
	/**
	 * Set default content width.
	 *
	 * @return void
	 */
	function hello_elementor_content_width() {
		$GLOBALS['content_width'] = apply_filters( 'hello_elementor_content_width', 800 );
	}
}
add_action( 'after_setup_theme', 'hello_elementor_content_width', 0 );

if ( ! function_exists( 'hello_elementor_add_description_meta_tag' ) ) {
	/**
	 * Add description meta tag with excerpt text.
	 *
	 * @return void
	 */
	function hello_elementor_add_description_meta_tag() {
		if ( ! apply_filters( 'hello_elementor_description_meta_tag', true ) ) {
			return;
		}

		if ( ! is_singular() ) {
			return;
		}

		$post = get_queried_object();
		if ( empty( $post->post_excerpt ) ) {
			return;
		}

		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $post->post_excerpt ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'hello_elementor_add_description_meta_tag' );

// Admin notice
if ( is_admin() ) {
	require get_template_directory() . '/includes/admin-functions.php';
}

// Settings page
require get_template_directory() . '/includes/settings-functions.php';

// Header & footer styling option, inside Elementor
require get_template_directory() . '/includes/elementor-functions.php';

if ( ! function_exists( 'hello_elementor_customizer' ) ) {
	// Customizer controls
	function hello_elementor_customizer() {
		if ( ! is_customize_preview() ) {
			return;
		}

		if ( ! hello_elementor_display_header_footer() ) {
			return;
		}

		require get_template_directory() . '/includes/customizer-functions.php';
	}
}
add_action( 'init', 'hello_elementor_customizer' );

if ( ! function_exists( 'hello_elementor_check_hide_title' ) ) {
	/**
	 * Check whether to display the page title.
	 *
	 * @param bool $val default value.
	 *
	 * @return bool
	 */
	function hello_elementor_check_hide_title( $val ) {
		if ( defined( 'ELEMENTOR_VERSION' ) ) {
			$current_doc = Elementor\Plugin::instance()->documents->get( get_the_ID() );
			if ( $current_doc && 'yes' === $current_doc->get_settings( 'hide_title' ) ) {
				$val = false;
			}
		}
		return $val;
	}
}
add_filter( 'hello_elementor_page_title', 'hello_elementor_check_hide_title' );

/**
 * BC:
 * In v2.7.0 the theme removed the `hello_elementor_body_open()` from `header.php` replacing it with `wp_body_open()`.
 * The following code prevents fatal errors in child themes that still use this function.
 */
if ( ! function_exists( 'hello_elementor_body_open' ) ) {
	function hello_elementor_body_open() {
		wp_body_open();
	}
}

// INICIALIZANDO O PROJETO *********************************************************************

// Adiciona o método de envio personalizado ao WooCommerce
function add_kangu_shipping_method( $methods ) {
    $methods['kangu_shipping'] = 'WC_Kangu_Shipping_Method';
    return $methods;
}
add_filter( 'woocommerce_shipping_methods', 'add_kangu_shipping_method' );

// Define a classe do método de envio personalizado
if ( ! class_exists( 'WC_Kangu_Shipping_Method' ) ) {
    class WC_Kangu_Shipping_Method extends WC_Shipping_Method {
        public function __construct() {
            $this->id                 = 'kangu_shipping'; 
            $this->method_title       = __( 'Kangu Shipping', 'woocommerce' ); 
            $this->method_description = __( 'Método de envio personalizado utilizando a API Kangu.', 'woocommerce' ); 
            
            $this->init();
        }

        // Inicializa as configurações
        function init() {
            // Carregar configurações
            $this->init_form_fields();
            $this->init_settings();

            $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
            $this->title   = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Kangu Shipping', 'woocommerce' );

            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }

        // Define os campos de formulário para o método de envio
        function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title'   => __( 'Habilitar/Desabilitar', 'woocommerce' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Habilitar Kangu Shipping', 'woocommerce' ),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title'       => __( 'Título', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Título a ser exibido ao cliente durante o checkout.', 'woocommerce' ),
                    'default'     => __( 'Kangu Shipping', 'woocommerce' ),
                    'desc_tip'    => true,
                ),
                'productcep' => array(
                    'title'       => __( 'CEP de Origem', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Informe o CEP de origem dos produtos.', 'woocommerce' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'api_key' => array(
                    'title'       => __( 'API Key', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Informe a chave da API Kangu.', 'woocommerce' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            );
        }

        // Calcula o custo de envio
        public function calculate_shipping( $package = array() ) {
            $cost = $this->get_shipping_cost( $package );

            // Verifica se o custo retornado é válido
            if ( $cost === 0 ) {
                $cost = 100; // Defina um custo padrão ou de fallback, como 100.
            }

            $rate = array(
                'id'    => $this->id,
                'label' => $this->title,
                'cost'  => $cost,
                'calc_tax' => 'per_item'
            );

            $this->add_rate( $rate );
        }

        private function get_shipping_cost( $package ) {
            $productcep = $this->get_option('productcep');
            $api_key = $this->get_option('api_key');
        
            if ( empty( $productcep ) || empty( $api_key ) ) {
                error_log('Kangu Shipping: CEP de origem ou API key não configurados.');
                return 0;
            }
        
            $url = 'https://portal.kangu.com.br/tms/transporte/simular';
            
            // Pega o valor declarado baseado no subtotal do carrinho
            $valorDeclarado = WC()->cart->get_subtotal();
            
            // Validações adicionais para garantir que os valores não estão vazios ou errados
            if ( empty( $package['destination']['postcode'] ) ) {
                error_log('Kangu Shipping: CEP de destino não está definido.');
                return 0;
            }

            if ( empty( WC()->cart->get_cart_contents_weight() ) ) {
                error_log('Kangu Shipping: Peso do carrinho está vazio.');
                return 0;
            }

            if ( $valorDeclarado <= 0 ) {
                error_log('Kangu Shipping: Valor declarado está inválido: ' . $valorDeclarado);
                return 0;
            }
        
            // Monta o JSON com a estrutura desejada
            $args = array(
                'method'  => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'token' => $api_key,
                ),
                'body'    => wp_json_encode( array(
                    'cepOrigem'   => $productcep,
                    'cepDestino'  => $package['destination']['postcode'],
                    'vlrMerc'     => $valorDeclarado,
                    'pesoMerc'    => WC()->cart->get_cart_contents_weight(),
                    'produtos'    => array_map( function( $item ) {
                        return array(
                            'peso'          => $item['data']->get_weight(), // Peso do produto
							'altura'        => $item['data']->get_height(), // Altura do produto
							'largura'       => $item['data']->get_width(),  // Largura do produto
							'comprimento'   => $item['data']->get_length(), // Comprimento do produto
							'valor'         => $item['data']->get_price(),  // Preço do produto
							'quantidade'    => $item['quantity'],           // Quantidade no carrinho
                        );
                    }, WC()->cart->get_cart() ),
                    'servicos'    => array( 'express' ),
                    'ordernar'    => 'prazo',
                ) ),
            );
        
            $response = wp_remote_post( $url, $args );
        
            if ( is_wp_error( $response ) ) {
                error_log('Kangu Shipping: Erro na chamada da API: ' . $response->get_error_message());
                return 0;
            }
        
            $body = wp_remote_retrieve_body( $response );
            $http_code = wp_remote_retrieve_response_code( $response );
            error_log('Kangu Shipping: HTTP Response Code: ' . $http_code);
            error_log('Kangu Shipping: Response Body: ' . $body);
            $data = json_decode( $body, true );
        
            if ( empty( $data ) || ! isset( $data['vlrFrete'] ) || $data['vlrFrete'] === null ) {
                error_log('Kangu Shipping: Resposta da API inválida ou valor de frete não encontrado.');
                if ( isset( $data['error'] ) ) {
                    error_log('Kangu Shipping: Erro retornado pela API: ' . $data['error']['mensagem']);
                }
                return 0;
            }
        
            $valorFrete = $data['vlrFrete'];
            error_log('Kangu Shipping: Valor do frete calculado: ' . $valorFrete);
            
            return $valorFrete;
        }
    }
}

// Inicializa o método de envio ao WooCommerce
function kangu_shipping_method_init() {
    error_log('Kangu Shipping Method Loaded');

    if ( class_exists( 'WC_Kangu_Shipping_Method' ) ) {
        add_action( 'woocommerce_shipping_init', 'add_kangu_shipping_method' );
    }
}
add_action( 'woocommerce_shipping_init', 'kangu_shipping_method_init' );
