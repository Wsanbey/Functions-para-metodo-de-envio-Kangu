<?php
// // INICIALIZANDO O PROJETO *********************************************************************
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
                'api_key' => array(
                    'title'       => __( 'API Key', 'woocommerce' ),
                    'type'        => 'text',
                    'description' => __( 'Informe a chave da API Kangu.', 'woocommerce' ),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
//                 'default_postcode' => array(
//                     'title'       => __( 'CEP Padrão', 'woocommerce' ),
//                     'type'        => 'text',
//                     'description' => __( 'Informe um CEP padrão a ser utilizado quando o produto não tiver um CEP de origem definido Ex.: 00000000.', 'woocommerce'),
//                     'default'     => '',
//                     'desc_tip'    => true,
//                 ),
                'default_days' => array(
                    'title'       => __( 'Dias Adicionais', 'woocommerce' ),
                    'type'        => 'number',
                    'description' => __( 'Número de dias adicionais a serem adicionados ao prazo de entrega.', 'woocommerce' ),
                    'default'     => 0,
                    'desc_tip'    => true,
                ),
                'extra_weight_cost' => array(
                    'title'       => __( 'Custo por Peso Adicional', 'woocommerce' ),
                    'type'        => 'number',
                    'description' => __( 'Custo adicional por kg além do peso base.', 'woocommerce' ),
                    'default'     => 0,
                    'desc_tip'    => true,
                    'custom_attributes' => array(
                        'step' => '0.01', // Permite casas decimais
                        'min'  => '0'
					),
                ),
                'handling_fee' => array(
                    'title'       => __( 'Taxa de Manuseio', 'woocommerce' ),
                    'type'        => 'number',
                    'description' => __( 'Taxa fixa de manuseio para cada pedido.', 'woocommerce' ),
                    'default'     => 0,
                    'desc_tip'    => true,
                    'custom_attributes' => array(
                        'step' => '0.01', // Permite casas decimais
                        'min'  => '0'
                    ),
                ),
                'cart_fee' => array(
                    'title'       => __( 'Taxa do Carrinho', 'woocommerce' ),
                    'type'        => 'number',
                    'description' => __( 'Taxa fixa ou percentual adicional baseada no subtotal do carrinho.', 'woocommerce' ),
                    'default'     => 0,
                    'desc_tip'    => true,
                    'custom_attributes' => array(
                        'step' => '0.01', // Permite casas decimais
                        'min'  => '0'
                    ),
                ),
				'section_title' => array(
				'title'       => '<h1>' . __( 'Configurar métodos de envio', 'woocommerce' ) . '</h1>',
				'type'        => 'title',
				'description' => __( 'Defina as configurações para os métodos de envio disponíveis.', 'woocommerce' ),
				), 		
				'pac_section_title' => array(
				'title'       => __( 'Correios PAC via Kangu', 'woocommerce' ),
				'type'        => 'title',
				),
				'pac_enabled' => array(
					'title'       => __( 'Ativar Correios PAC via Kangu', 'woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Ativar este método', 'woocommerce' ),
					'default'     => 'yes',
				),
				'pac_display_name' => array(
					'title'       => __( 'Nome de exibição para Correios PAC via Kangu', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Nome que será exibido para este método de envio durante o checkout.', 'woocommerce' ),
					'default'     => __( 'Correios PAC via Kangu', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'correios_sedex_section_title' => array(
					'title'       => __( 'Correios Sedex via Kangu', 'woocommerce' ),
					'type'        => 'title',
				),
				'correios_sedex_enabled' => array(
					'title'       => __( 'Ativar Correios Sedex via Kangu', 'woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Ativar este método', 'woocommerce' ),
					'default'     => 'yes',
				),
				'correios_sedex_display_name' => array(
					'title'       => __( 'Nome de exibição para Correios Sedex via Kangu', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Nome que será exibido para este método de envio durante o checkout.', 'woocommerce' ),
					'default'     => __( 'Correios Sedex via Kangu', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'loggi_section_title' => array(
					'title'       => __( 'Loggi via Kangu', 'woocommerce' ),
					'type'        => 'title',
				),
				'loggi_enabled' => array(
					'title'       => __( 'Ativar Loggi via Kangu', 'woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Ativar este método', 'woocommerce' ),
					'default'     => 'yes',
				),
				'loggi_display_name' => array(
					'title'       => __( 'Nome de exibição para Loggi via Kangu', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Nome que será exibido para este método de envio durante o checkout.', 'woocommerce' ),
					'default'     => __( 'Loggi via Kangu', 'woocommerce' ),
					'desc_tip'    => true,
				),
				'jadlog_section_title' => array(
					'title'       => __( 'Jadlog via Kangu', 'woocommerce' ),
					'type'        => 'title',
				),
				'jadlog_enabled' => array(
					'title'       => __( 'Ativar Jadlog via Kangu', 'woocommerce' ),
					'type'        => 'checkbox',
					'label'       => __( 'Ativar este método', 'woocommerce' ),
					'default'     => 'yes',
				),
				'jadlog_display_name' => array(
					'title'       => __( 'Nome de exibição para Jadlog via Kangu', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Nome que será exibido para este método de envio durante o checkout.', 'woocommerce' ),
					'default'     => __( 'Jadlog via Kangu', 'woocommerce' ),
					'desc_tip'    => true,
				), 
            );
        }
		
//**************************-- FINAL DA: Seção dos metódos de envios --*************************************** 	

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
				// Ordena as opções de envio pelo prazo de entrega em ordem crescente
				usort($shipping_options, function($a, $b) {
					return $a['prazoEnt'] - $b['prazoEnt'];
				});

				$rates = array(); // Array temporário para armazenar as taxas de envio

				// Obtém Dias Adicionais das configurações
				$additional_days = intval( $this->get_option('default_days') );  

				// Obtém a Custo por Peso Adicional das configurações
				$extra_weight_cost = floatval( $this->get_option('extra_weight_cost') );

				// Obtém o Limite de Peso das configurações (exemplo: 5kg)
				$weight_limit = floatval( $this->get_option('weight_limit') );

				// Obtém a Taxa de Manuseio das configurações
				$handling_fee = floatval( $this->get_option('handling_fee') );

				// Obtém a Taxa de Taxa do Carrinho das configurações (percentual)
				$cart_fee = floatval( $this->get_option('cart_fee') );

				// Define os métodos de envio disponíveis e suas configurações dinâmicas
				$shipping_methods = array(
					'Correios PAC via Kangu' => array(
						'enabled' => $this->get_option('pac_enabled') === 'yes',
						'display_name' => $this->get_option('pac_display_name', 'Correios PAC via Kangu'),
					),
					'Correios Sedex via Kangu' => array(
						'enabled' => $this->get_option('correios_sedex_enabled') === 'yes',
						'display_name' => $this->get_option('correios_sedex_display_name', 'Correios Sedex via Kangu'),
					),
					'Loggi via Kangu' => array(
						'enabled' => $this->get_option('loggi_enabled') === 'yes',
						'display_name' => $this->get_option('loggi_display_name', 'Loggi via Kangu'),
					),
					'Jadlog via Kangu' => array(
						'enabled' => $this->get_option('jadlog_enabled') === 'yes',
						'display_name' => $this->get_option('jadlog_display_name', 'Jadlog via Kangu'),
					),	
					// Adicione mais métodos de envio conforme necessário
				);

				// Adiciona as taxas de frete ordenadas
				foreach ($shipping_options as $option) {

					// Nome da transportadora vindo da API
					$descricao_api = $option['descricao'];
					
					// Obtém o valor do frete vindo da API
					$cost = isset($option['vlrFrete']) ? floatval($option['vlrFrete']) : 0;

					// Obtém o peso total dos itens no carrinho
					$cart_weight = WC()->cart->get_cart_contents_weight();

					// Calcula o peso adicional, se o peso do carrinho ultrapassar o limite definido
					$additional_weight = $cart_weight > $weight_limit ? $cart_weight - $weight_limit : 0;

					// Calcula o custo adicional por peso extra
					$extra_weight_cost_total = $additional_weight * $extra_weight_cost;

					// Aplica a taxa do carrinho (percentual) ao custo do frete
					$cart_fee_amount = ($cost * $cart_fee) / 100;

					// Calcula o custo total do frete incluindo peso adicional, taxa de manuseio e taxa do carrinho
					$total_shipping_cost = $cost + $cart_fee_amount + $handling_fee + $extra_weight_cost_total;

					// Adiciona os dias adicionais ao prazo de entrega
					$delivery_time = intval($option['prazoEnt']) + $additional_days;

					// Loop para verificar correspondência com os métodos de envio configurados
					foreach ( $shipping_methods as $method_key => $method_data ) {
						//error_log("descricao_api foi: " .$method_key);
						//error_log("descricao_api JOSON foi: " .$descricao_api);
						

						if ( $method_data['enabled'] && strtolower($descricao_api) === strtolower($method_key) ) {
							// Se houver correspondência, use o nome personalizado
							$descricao_api = $method_data['display_name'];

							// Valida se o prazo de entrega e o custo são válidos
							if ( $cost > 0 && $delivery_time > 0 ) {
									// Formata a descrição do método de envio para exibição
									$description = sprintf(
										"%s - R$ %.2f (Entrega em até %d dias úteis)",
										$descricao_api,
										$total_shipping_cost,  // Usando o custo total calculado
										$delivery_time
									);

									// Cria a taxa de envio que será adicionada
									$rate = array(
										'id'    => $option['idSimulacao'],
										'label' => $description,
										'cost'  => $total_shipping_cost, // Define o custo total do frete calculado
										'calc_tax' => 'per_item'
									);
								
									// Adiciona a taxa de envio
									$this->add_rate($rate);								 
							}

							break; // Pare o loop após encontrar a correspondência
						}
					} 
				}

				// Verifica se o array de taxas está vazio
        if ( empty($rates) ) {
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
        } else {
            // Armazena as taxas de frete na sessão para reutilização
            WC()->session->set($cache_key, $rates);

            // Adiciona todas as taxas de frete armazenadas
            foreach ($rates as $rate) {
                $this->add_rate($rate);
            }
        }

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

		
        private function get_shipping_cost( $package ) {
			$api_key = $this->get_option('api_key');
			$cache_key = 'kangu_shipping_' . md5( $package['destination']['postcode'] . WC()->cart->get_cart_contents_weight() . WC()->cart->get_subtotal() );
		
			// Tenta obter os dados do cache
			$cached_data = get_transient( $cache_key );
			if ( $cached_data !== false ) {
				return $cached_data;
			}
		
			if ( empty( $api_key ) ) {
				error_log('Kangu Shipping: API key não configurada.');
				return array();
			}
		
			// Obtém o CEP de origem para cada produto no carrinho
			$product_ceps = array();
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$product_id = $cart_item['product_id'];
				$product_cep = get_post_meta( $product_id, 'product_postcode', true );
				if ( !empty( $product_cep ) ) {
					$product_ceps[] = $product_cep;
				}
			}
		
			// Usa o primeiro CEP encontrado, ou um valor padrão se nenhum for encontrado
			$productcep = !empty( $product_ceps ) ? $product_ceps[0] : $this->get_option('productcep');
		
			if ( empty( $productcep ) ) {
				error_log('Kangu Shipping: CEP de origem não configurado.');
				return array();
			}
		
			$url = 'https://portal.kangu.com.br/tms/transporte/simular';
			
			// Calcula o valor declarado para todos os itens no carrinho
			$valorDeclarado = 0;
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				$valorDeclarado += $cart_item['data']->get_price() * $cart_item['quantity'];
			}
			$valorDeclarado = min($valorDeclarado, 3500.00);
			
			$args = array(
				'method'  => 'POST',
				'timeout' => 30,  // Aumenta o timeout para 30 segundos
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
							'peso'          => $item['data']->get_weight(),
							'altura'        => $item['data']->get_height(),
							'largura'       => $item['data']->get_width(),
							'comprimento'   => $item['data']->get_length(),
							'valor'         => $valorDeclarado,
							'quantidade'    => $item['quantity'],
						);
					}, WC()->cart->get_cart() ),
					'servicos'    => array( 'express' ),
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
		
			// Armazena os dados no cache por 1 hora
			set_transient( $cache_key, $data, 10 * MINUTE_IN_SECONDS ); 
		
			return $data;
		}		
    }
}

// Inicializa o método de envio ao WooCommerce
function kangu_shipping_method_init() {
    error_log('Kangu Shipping Method Loaded');
}
add_action( 'woocommerce_shipping_init', 'kangu_shipping_method_init' );

