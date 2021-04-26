<?php
/**
 * REST API Product Attributes controller
 *
 * Handles requests to the products/attributes endpoint.
 *
 * @package WooCommerce\RestApi
 * @since    3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Product Attributes controller class.
 *
 * @package WooCommerce\RestApi
 * @extends WC_REST_Controller
 */
class WC_REST_Product_Attributes_V1_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'products/attributes';

	/**
	 * Attribute name.
	 *
	 * @var string
	 */
	protected $attribute = '';

	/**
	 * Cached taxonomies by attribute id.
	 *
	 * @var array
	 */
	protected $taxonomies_by_id = array();

	/**
	 * Register the routes for product attributes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array_merge(
						$this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
						array(
							'name' => array(
								'description' => __( 'Name for the resource.', 'woocommerce' ),
								'type'        => 'string',
								'required'    => true,
							),
						)
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param( array( 'default' => 'view' ) ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => array(
						'force' => array(
							'default'     => true,
							'type'        => 'boolean',
							'description' => __( 'Required to be true, as resource does not support trashing.', 'woocommerce' ),
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/batch',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'batch_items' ),
					'permission_callback' => array( $this, 'batch_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				'schema' => array( $this, 'get_public_batch_schema' ),
			)
		);
	}

	/**
	 * Check if a given request has access to read the attributes.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'attributes', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to create a attribute.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'attributes', 'create' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you cannot create new resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to read a attribute.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! $this->get_taxonomy( $request ) ) {
			return new WP_Error( 'woocommerce_rest_taxonomy_invalid', __( 'Resource does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		if ( ! wc_rest_check_manager_permissions( 'attributes', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to update a attribute.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! $this->get_taxonomy( $request ) ) {
			return new WP_Error( 'woocommerce_rest_taxonomy_invalid', __( 'Resource does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		if ( ! wc_rest_check_manager_permissions( 'attributes', 'edit' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_update', __( 'Sorry, you cannot update resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete a attribute.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! $this->get_taxonomy( $request ) ) {
			return new WP_Error( 'woocommerce_rest_taxonomy_invalid', __( 'Resource does not exist.', 'woocommerce' ), array( 'status' => 404 ) );
		}

		if ( ! wc_rest_check_manager_permissions( 'attributes', 'delete' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'Sorry, you are not allowed to delete this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access batch create, update and delete items.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function batch_items_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'attributes', 'batch' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_batch', __( 'Sorry, you are not allowed to batch manipulate this resource.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Get all attributes.
	 *
	 * @param WP_REST_Request $request The request to get the attributes from.
	 * @return array
	 */
	public function get_items( $request ) {
		$attributes = wc_get_attribute_taxonomies();
		$data       = array();
		foreach ( $attributes as $attribute_obj ) {
			$attribute = $this->prepare_item_for_response( $attribute_obj, $request );
			$attribute = $this->prepare_response_for_collection( $attribute );
			$data[]    = $attribute;
		}

		$response = rest_ensure_response( $data );

		// This API call always returns all product attributes due to retrieval from the object cache.
		$response->header( 'X-WP-Total', count( $data ) );
		$response->header( 'X-WP-TotalPages', 1 );

		return $response;
	}

	/**
	 * Create a single attribute.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Request|WP_Error
	 */
	public function create_item( $request ) {
		global $wpdb;

		$id = wc_create_attribute(
			array(
				'name'         => $request['name'],
				'slug'         => wc_sanitize_taxonomy_name( stripslashes( $request['slug'] ) ),
				'type'         => ! empty( $request['type'] ) ? $request['type'] : 'select',
				'order_by'     => ! empty( $request['order_by'] ) ? $request['order_by'] : 'menu_order',
				'has_archives' => true === $request['has_archives'],
			)
		);

		// Checks for errors.
		if ( is_wp_error( $id ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', $id->get_error_message(), array( 'status' => 400 ) );
		}

		$attribute = $this->get_attribute( $id );

		if ( is_wp_error( $attribute ) ) {
			return $attribute;
		}

		$this->update_additional_fields_for_object( $attribute, $request );

		/**
		 * Fires after a single product attribute is created or updated via the REST API.
		 *
		 * @param stdObject       $attribute Inserted attribute object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating attribute, false when updating.
		 */
		do_action( 'woocommerce_rest_insert_product_attribute', $attribute, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $attribute, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( '/' . $this->namespace . '/' . $this->rest_base . '/' . $attribute->attribute_id ) );

		return $response;
	}

	/**
	 * Get a single attribute.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Request|WP_Error
	 */
	public function get_item( $request ) {
		$attribute = $this->get_attribute( (int) $request['id'] );

		if ( is_wp_error( $attribute ) ) {
			return $attribute;
		}

		$response = $this->prepare_item_for_response( $attribute, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Update a single term from a taxonomy.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Request|WP_Error
	 */
	public function update_item( $request ) {
		global $wpdb;

		$id     = (int) $request['id'];
		$edited = wc_update_attribute(
			$id,
			array(
				'name'         => $request['name'],
				'slug'         => wc_sanitize_taxonomy_name( stripslashes( $request['slug'] ) ),
				'type'         => $request['type'],
				'order_by'     => $request['order_by'],
				'has_archives' => $request['has_archives'],
			)
		);

		// Checks for errors.
		if ( is_wp_error( $edited ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_edit', $edited->get_error_message(), array( 'status' => 400 ) );
		}

		$attribute = $this->get_attribute( $id );

		if ( is_wp_error( $attribute ) ) {
			return $attribute;
		}

		$this->update_additional_fields_for_object( $attribute, $request );

		/**
		 * Fires after a single product attribute is created or updated via the REST API.
		 *
		 * @param stdObject       $attribute Inserted attribute object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating attribute, false when updating.
		 */
		do_action( 'woocommerce_rest_insert_product_attribute', $attribute, $request, false );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $attribute, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Delete a single attribute.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$force = isset( $request['force'] ) ? (bool) $request['force'] : false;

		// We don't support trashing for this type, error out.
		if ( ! $force ) {
			return new WP_Error( 'woocommerce_rest_trash_not_supported', __( 'Resource does not support trashing.', 'woocommerce' ), array( 'status' => 501 ) );
		}

		$attribute = $this->get_attribute( (int) $request['id'] );

		if ( is_wp_error( $attribute ) ) {
			return $attribute;
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $attribute, $request );

		$deleted = wc_delete_attribute( $attribute->attribute_id );

		if ( false === $deleted ) {
			return new WP_Error( 'woocommerce_rest_cannot_delete', __( 'The resource cannot be deleted.', 'woocommerce' ), array( 'status' => 500 ) );
		}

		/**
		 * Fires after a single attribute is deleted via the REST API.
		 *
		 * @param stdObject        $attribute     The deleted attribute.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'woocommerce_rest_delete_product_attribute', $attribute, $response, $request );

		return $response;
	}

	/**
	 * Prepare a single product attribute output for response.
	 *
	 * @param obj             $item Term object.
	 * @param WP_REST_Request $request The request to process.
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'           => (int) $item->attribute_id,
			'name'         => $item->attribute_label,
			'slug'         => wc_attribute_taxonomy_name( $item->attribute_name ),
			'type'         => $item->attribute_type,
			'order_by'     => $item->attribute_orderby,
			'has_archives' => (bool) $item->attribute_public,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->add_additional_fields_to_object( $data, $request );
		$data    = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item ) );

		/**
		 * Filter a attribute item returned from the API.
		 *
		 * Allows modification of the product attribute data right before it is returned.
		 *
		 * @param WP_REST_Response  $response  The response object.
		 * @param object            $item      The original attribute object.
		 * @param WP_REST_Request   $request   Request used to generate the response.
		 */
		return apply_filters( 'woocommerce_rest_prepare_product_attribute', $response, $item, $request );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param object $attribute Attribute object.
	 * @return array Links for the given attribute.
	 */
	protected function prepare_links( $attribute ) {
		$base  = '/' . $this->namespace . '/' . $this->rest_base;
		$links = array(
			'self'       => array(
				'href' => rest_url( trailingslashit( $base ) . $attribute->attribute_id ),
			),
			'collection' => array(
				'href' => rest_url( $base ),
			),
		);

		return $links;
	}

	/**
	 * Get the Attribute's schema, conforming to JSON Schema.
	 *
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'product_attribute',
			'type'       => 'object',
			'properties' => array(
				'id'           => array(
					'description' => __( 'Unique identifier for the resource.', 'woocommerce' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'         => array(
					'description' => __( 'Attribute name.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'slug'         => array(
					'description' => __( 'An alphanumeric identifier for the resource unique to its type.', 'woocommerce' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'arg_options' => array(
						'sanitize_callback' => 'sanitize_title',
					),
				),
				'type'         => array(
					'description' => __( 'Type of attribute.', 'woocommerce' ),
					'type'        => 'string',
					'default'     => 'select',
					'enum'        => array_keys( wc_gf_-PA30 �mq�d��^�?�x 
��#:���{-B�V�<M�  �        S]	H�,�����������*��*��ۢ����������
�� � �� �?���� ����
���LQY=���G"�!���LQٟ�ݽ��ݗY��QQ���������% BѤ��C��0  9��<KC�~�	��/��)P�i8�AF'��ɵ<,����tW��D
��.�8VAXN�b��?�<&ޔ$+N)W���rX����IA� ��0O1��4xX�e�_i�����+"]q����&&|Q�_尔�4\�W�e��Z�06@�
0z��R��B�#1L!X��ŽkL���S�`pHXH8f׭7BL`Є��A�eX �p�(���~�[��BÈzÈ�KoQ�<��YnX��V�5�	u G0ۃB`P}��>�������!6PR�2��3�r���2�E��Q�&zJ4��E+:K4����h�Ќ�L���x&!!fC��5)2$�C���"�[Tu3�1-⾀%����7�`Q6���<X�CpH� AN	0 �� ���Á����p@�U�pH!�n5�Z��F ,;�Ia�A8��h���yA0�&�h�/�y"|(,Š���ʴX7��`P��!0��o)0���� ��A1(vpp0(Šp���Z߹��|�:��z	��FW��KuP�Ⲗ+��a�E�1�%����`$������c�Z#ψ}U���	�4_�s��z��=Ԭ�xvXQ(�P�/�78���!��K~��${Ak��sx	�@ |�֐��xVV4S� GuSw SW����a�5��h^k��Vic�4�H{�}�(�MC�X��Iby����B"i{�E�i[biHR�M%mB�Z�6�=��}�Ҟ��y�6�%m�������S{h��4ibi�}��h�����%���((�ؐ�Tۧy%��ѵ���VxB�Ih�(��I41�'D�nC_x��>�>���j-�j��i�
/��.��/@�[xT� P�H�Ȁ@�BG��SEOuN�-��ō0��Q|(��I 2��F�cs������<RIڄ���Fg���pj1h��5���Lj���
5I��%5�E
7�%4�i�)���)�D#��-��k-&c�%�Ģ��ii,JXo�5Ur�P�yli�|��Qi�dB�Q��Y`4L+��c��6�b*����
��Ʀ0fKY���X�ʢ��d�浰�b��E�l��"�b��4�F��Ԓ&�,��Ѥ�=M{�ӤX�-eoN-i�-�l�[�H�,���c�۔a;6@��c�,a�	�B��#P���	*�DOF�ztԥQ���Kk�7���Ri{O�&K[��RU����*���{nQXQQ�M%4����ϣ�D��D���(\B�\8��p���al��m��ҡ2-�1���̰����}�0L�0��N��#��Ќ ���ϐ��T��;P��oƻ�r�OI�� �&2��Y���iؿ!��ԠS`l1������Á	���`�T���װ�F����Ȍ�FD��P� ~���PeK2㰸�IN���%�%h��'ѝy��$�Ar�4/]*G��(����̄�����b����]��s������b���(-�?Z\�җ���C8*^E����2�=~vu�	c����=y��t����?}�DbT���d]��b���Y9�������D�������13xUX,w�y(�X�(6�[ŋ�u��ۼ��T�Nf����TťJ\k\v�����bC�U��Տ�<}������f]��n-����@b�H�d&W#�� ո�R�)a:�(H�{ :�;�NT$��`����q����D7!��J��2f����b)'�Nd׌a!�e�����wƺ��/o���mfF_�b���u���r�gQe9y~�:���,qd��fY��	��ov��	�D�HԂ�	P��Wb���{C���r�ܮ��7|��6
��.�E�ݹ�ŋ�OX������Ѩ�Q��RU�Mӧ#E���8z}%���8}�;w��p��G��<}���:�}�����'���?y���-���w�޻��ꇏ?y���-v�;w�����9��U��Ű���H�U?y���16��Ok3]��ܻ�-L���G�j��u_�c� 0�RV�4���
,�A�M��U8�l�Ƿy)�?�û�&O��Ǳ�������1x�;8�x]�T���U��z�64W��n5�[�]����)�O��^�e7�Q@�M����tx��h�1>f!>a�__C����@T?��O���:$�f }9�WxP*سG�R"�� ���'Ϟ_%��=~z���o���_E�y��'O��'t7||UR��65z��b01�����B\+���샭�
���^�}面��}�~9����O�>+<(*<��]���D����Ϟ�"�Nk߹{����>z����D���w�;w��p��G�'��Ϟ�"m׾s���W?|����g�o��k�y���g�iN�qϞ�"ÐɬYF�>�P�m��a�:��G����� --[����o �������z@����j9�K�`���j�n*�����ٲ��ꇏ?y���-xk����W?|L��Uɸ*��[��'Z��)��n3��(:=�yϞ�b��ܽ������\�� ����'O��B���aa�M� �6�z�\����%��BP�ν�4�x�h���-���
��ܻ5DD�_��Ye����@b7~��\5��ѱ�����YemN����U�v�GO�� �Î;T�6\iEз��w��B�<#Qa��M5�Ay@�`<�~r�Ld��u��x@M R���ωF@��X��N��f�A�,Xw�$N�Bahƭ����f��9����9X�K兲�
E\8T)���2eE���FK���H���u��j���n�58Q�"���pl��Z!z&(i��m�A������X�l��U�Ih����-CT��z�P�S�$�b�E�ڎ�67�ş�Fh#�1CS���Cò��Hh�4�hi��qh�7R#��Gz�-n�42�@�J�97~M@#��	�bT��Fd#�I�c���Ɛ�,
m�#"��C >������$z yM��"`�O�>�Jf���TJf���TZf��L9�L9�LѢ�FƲ�H�?�&K�"5O�c�d=V(�$����ӉS._WQ&,��>�S��:;r�%��	���i����q8xN�2�9��z2�����I}:a���O��jŔ�q��) �/J��(��D:��&Y��4p��̕p��q�78���4�H �ɋX2�i���oQ�+�
<3]!�v�<����%�H�<Ԉ3�=�� �2��g�=+�w5�A� /%N���k�?����D*j