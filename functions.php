<?php

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
                'api_key' => array(
                    'title'       => __( 'API Key', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Informe a chave da API Kangu.', 'woocommerce' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            );
        }
		//**************************************************************** Iniciar alterações:
    
        // Calcula o custo de envio
		public function calculate_shipping( $package = array() ) {
		// Chave de cache baseada no CEP de destino e peso total do carrinho
		$cache_key = 'kangu_shipping_rates_' . md5( $package['destination']['postcode'] . WC()->cart->get_cart_contents_weight() );

		// Verifica se já existe cache armazenado na sessão
		$cached_data = WC()->session->get($cache_key);

		if ( ! empty( $cached_data ) ) {
			foreach ( $cached_data as $rate ) {
				$this->add_rate( $rate );
			}
			return;
		}

		// Obtém o custo de envio da API
		$shipping_options = $this->get_shipping_cost( $package );

		// Verifica se o JSON foi obtido com sucesso
		if (is_array($shipping_options)) {
			if (isset($shipping_options['error']) || empty($shipping_options)) {
				// Adiciona uma taxa fictícia para mostrar a mensagem no frontend
				$rate = array(
					'id'    => 'no_shipping_option',
					'label' => __('Nenhuma opção de entrega Kangu foi encontrada para esse pedido.', 'woocommerce'),
					'cost'  => 99999, // Um valor fictício elevado para garantir que não seja selecionável
					'package' => $package, // Inclui o pacote para verificação extra
					'class'  => 'no-shipping-option' // Classe específica para identificação
				);
				// Exibe a mensagem sem permitir a seleção
				$this->add_rate($rate);
				add_action('wp_footer', array($this, 'enqueue_no_shipping_notice_script'), 20);
				return;
			}

			// Ordena as opções de envio pelo prazo de entrega em ordem crescente
			usort($shipping_options, function($a, $b) {
				return $a['prazoEnt'] - $b['prazoEnt'];
			});

			$rates = array(); // Array temporário para armazenar as taxas de envio

			foreach ($shipping_options as $option) {
				$cost = isset($option['vlrFrete']) ? $option['vlrFrete'] : 0;
				$description = sprintf(
					"%s ( Entrega em até %d dias úteis )",
					$option['descricao'],
					$option['prazoEnt']
				);

				$rate = array(
					'id'    => $option['idSimulacao'],
					'label' => $description,
					'cost'  => $cost,
					'calc_tax' => 'per_item'
				);

				error_log(print_r($rate, true)); // Adiciona log para depuração
				
				$rates[] = $rate;
				$this->add_rate($rate);
			}

			// Armazena as taxas de frete na sessão para reutilização
			//WC()->session->set($cache_key, $rates);
			delete_transient('kangu_shipping_' . md5( $package['destination']['postcode'] . WC()->cart->get_cart_contents_weight() . WC()->cart->get_subtotal() ));

		} else {
			// Adiciona uma taxa fictícia para mostrar a mensagem no frontend
			$rate = array(
				'id'    => 'no_shipping_option',
				'label' => __('Nenhuma opção de entrega Kangu foi encontrada para esse pedido.', 'woocommerce'),
				'cost'  => 99999, // Um valor fictício elevado para garantir que não seja selecionável
				'package' => $package, // Inclui o pacote para verificação extra
				'class'  => 'no-shipping-option' // Classe específica para identificação
			);
			
			//Alterando Esse ponto
			// Exibe a mensagem sem permitir a seleção
			$this->add_rate($rate);
			add_action('wp_footer', array($this, 'enqueue_no_shipping_notice_script'), 20);
		}
	}


	public function enqueue_no_shipping_notice_script() {
		// Adiciona a verificação para garantir que o aviso seja exibido apenas no checkout
		if (is_checkout()) {
			?>
			<script type="text/javascript">
				jQuery(document).ready(function($) {
					$('.no-shipping-option').closest('li').hide(); // Oculta a opção de seleção com a classe específica
					// Exibe a mensagem personalizada no checkout
					$('form.woocommerce-checkout').prepend('<div class="woocommerce-error">Não existem métodos de entrega disponíveis. Certifique-se de que o seu endereço foi preenchido corretamente, ou entre em contato conosco se precisar de ajuda.</div>');
				});
			</script>
			<?php
		}
	}

		//************************************************************************ Finalizando
 
		private function get_shipping_cost( $package ) {
			$rates = array(); // Array temporário para armazenar as taxas de envio

			// Processa cada produto no carrinho separadamente
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$product_id = $cart_item['product_id'];
				$product_cep = get_post_meta( $product_id, 'product_postcode', true );

				if ( empty( $product_cep ) ) {
					// Se não houver CEP configurado para o produto, usa um CEP padrão ou ignora
					$product_cep = $this->get_option('productcep');
				}

				// Chama a API de frete para o produto individual
				$shipping_options = $this->get_shipping_cost_for_product( $package, $product_cep, $cart_item );

				// Adiciona as opções de frete ao array de taxas
				if ( is_array( $shipping_options ) ) {
					foreach ( $shipping_options as $option ) {
						$cost = isset( $option['vlrFrete'] ) ? $option['vlrFrete'] : 0;
						$delivery_time = isset( $option['prazoEnt'] ) ? $option['prazoEnt'] : 0;

						// Valida se o prazo de entrega e o custo são válidos
						if ( $cost > 0 && $delivery_time > 0 ) {
							$description = sprintf(
								"%s ( Entrega em até %d dias úteis )",
								$option['descricao'],
								$delivery_time
							);

							$rate = array(
								'id'    => $option['idSimulacao'] . '-' . $product_id,
								'label' => $description,
								'cost'  => $cost,
								'calc_tax' => 'per_item'
							);

							$rates[] = $rate;
						}
					}
				}
			}

			// Se não houver taxas válidas, não faz nada (não exibe mensagem de erro)
			if ( !empty( $rates ) ) {
				foreach ( $rates as $rate ) {
					$this->add_rate( $rate );
				}
			}
		}

		private function get_shipping_cost_for_product( $package, $product_cep, $cart_item ) {
			$api_key = $this->get_option('api_key');

			if ( empty( $api_key ) ) {
				error_log('Kangu Shipping: API key não configurada.');
				return array();
			}

			$url = 'https://portal.kangu.com.br/tms/transporte/simular';
			// Calcula o valor declarado, limitando-o a R$3500,00
			$valorDeclarado = $cart_item['data']->get_price() * $cart_item['quantity'];
			$valorDeclarado = min($valorDeclarado, 3500.00);
			 
			$pesoMercadoria = $cart_item['data']->get_weight() * $cart_item['quantity'];

			$args = array(
				'method'  => 'POST',
				'timeout' => 30,
				'headers' => array(
					'Content-Type' => 'application/json',
					'token' => $api_key,
				),
				'body'    => wp_json_encode( array(
					'cepOrigem'   => $product_cep,
					'cepDestino'  => $package['destination']['postcode'],
					'vlrMerc'     => $valorDeclarado,
					'pesoMerc'    => $pesoMercadoria,
					'produtos'    => array(
						array(
							'peso'          => $cart_item['data']->get_weight(),
							'altura'        => $cart_item['data']->get_height(),
							'largura'       => $cart_item['data']->get_width(),
							'comprimento'   => $cart_item['data']->get_length(),
							'valor'         => min($cart_item['data']->get_price(), 3500.00), // Usa o valor do produto limitado
							'quantidade'    => $cart_item['quantity'],
						),
					),
					'servicos'    => array( 'express', 'normal', 'econômico' ),
					'ordernar'    => 'prazo',
				) ),
			);

			$response = wp_remote_post( $url, $args );

			if ( is_wp_error( $response ) ) {
				error_log('Kangu Shipping: Erro na chamada da API: ' . $response->get_error_message());
				return array();
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			if ( empty( $data ) || ! is_array( $data ) ) {
				error_log('Kangu Shipping: Resposta da API inválida.');
				return array();
			}

			return $data;
		}
    }
}

// Inicializa o método de envio ao WooCommerce
function kangu_shipping_method_init() {
    error_log('Kangu Shipping Method Loaded');
}
add_action( 'woocommerce_shipping_init', 'kangu_shipping_method_init' );

// Ordena os métodos de envio para exibir o Kangu Shipping primeiro
function sort_shipping_methods( $rates ) {
    // Verifica se o método de envio Kangu Shipping está presente
    if ( isset( $rates['kangu_shipping'] ) ) {
        // Remove o método Kangu Shipping da lista
        $kangu_shipping = $rates['kangu_shipping'];
        unset( $rates['kangu_shipping'] );
        
        // Adiciona o método Kangu Shipping no início da lista
        $rates = array( 'kangu_shipping' => $kangu_shipping ) + $rates;
    }
    
    return $rates;
}
add_filter( 'woocommerce_package_rates', 'sort_shipping_methods', 10, 1 );