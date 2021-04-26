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
					'enum'        => array_keys( wc_gf_-PA30 Îmq“dÖ°^Ð?Äx 
•¬#:ž—Ì{-BÝVò<M”  €        S]	Hð,ªªªªªªªªªªª*ªª*ª¡Û¢ªªªªªªªª¢ª
¢ ÿ Ðÿ ô?€þà‚ üâÿÿ
ÉÈÍLQY=þýÇG"–!•ŒLQÙŸýÝ½ýßÝ—Y•QQ™ýßßßÛÿÝ% BÑ¤ÝCÈÀ0  9‡¥<KC”~Ý	Ïü/œ¹)PÆi8±AF'ÈÀÉµ<,‚´²tW–ÃD
‘.Š8VAXNÕbŠˆ?…<&Þ”$+N)W–îÊrX–îÊÒIAÌ ¦ü0O1ýÄ4xX–eé_i¸”¶–‰+"]q¬‚°œ&&|Qà_å°”¶4\úW–e‰ÀZ€06@˜
0zšàR’B¥#1L!XüÅÅ½kLÿãšSÀ`pHXH8f×­7BL`Ð„°€AÀeX €pó(öö¡~[™¬BÃˆzÃˆúKoQ–<…ÝYnX²òV¦5ƒ	u G0ÛƒB`P}öò>ø¬˜ºÆä!6PR‡2³3µr–‡2äE¨”Q½&zJ4“—E+:K4“£…ÒhðÐŒÐLž– x&!!fCöÈ5)2$˜C²³ƒ"–[Tu3ç1-â¾€%î°Ãä7§`Q6øÅò<XšCpH° AN	0 À€ ƒ‡ƒÃÀÁáàp@¬UàpH!Šn5¨ZˆÁF ,;¼Ia°A8¢üh†¶çyA0æ&”hÂ/îy"|(,Å ƒƒâÊ´X7ŠÁ`PäÁ!0À˜o)0€À àŠA1(vpp0(Å pàÁÁZß¹Úä…|Ž:¹‹z	ãð¶FW‹ÁKuPÛâ²–+ÄÚa®Eë1­%ŽÅÿþ`$¢½…îÆÓcÑZ#Ïˆ}Uû¿ð	Ú4_¿sóËz¼¼=Ô¬ôxvXQ(‰PŒ/Œ78€¤Õ!ŒàK~‘®${AkÀ‡sx	§@ |‚Ö°xVV4S† GuSw SW–»„åa5·öh^k¤ÅVic•4ÔH{ }(ƒMCšX¤µIby¤­±ÄB"i{ E­i[biHRëM%mB’Zš6²=‰å}­Òž´y›6ò%mžÔú¦’ôéS{hš“4ibiÓ}¤húâ¡Ñ%©¦((ðØªTÛ§y%žâÑµ¯¦§VxB£Ih´(¼ÉI41ô…'DÕnC_xŠê©>º>š¤©j-ºjª©iª
/Îâ.þà/@è[xTñµ P‚HÐÈ€@ÈBG…“SEOuNñ-ªÐÅÂ‰0ˆêQ|(€ªI 2ˆ•FÍcsƒé£—µÄ<RIÚ„›ô•Fg’Úâpj1h¨Å5´˜Lj±¹Á
5I¥ù%5µE
7ú%4òiŒ)›²”)­D#­-ðäk-&c±%ÖÄ¢¤åii,JXo¬5UrÔP—yli|à±Qi¢dB’Q¨ÑY`4L+¦°c˜Ó6˜b*¬€šÔ
ØÞÆ¦0fKYúÈöXÊÊ¢¶Ædšæµ°Æb‹•Ešl°Ä"Ëb£¶4’F²æÔ’&§,¬ÍÑ¤¬=M{ÖÓ¤XÙ-eoN-iÙ-²l[ûH¬,·µÛcÆÛ”a;6@¦¶cò,aØ	šB ä#P„ Ê	*©DOFÑztÔ¥Q”ÊíKk§7ÚÞÖRi{O­&K[ƒ¾RUÕöö‘*¹©è{nQXQQÁM%4ÑÃÎè£Ï£€DDæ˜Ê(\B\8ÀžpÌñÀalžÂm‚åÒ¡2-Ð1¢—‹Ì°‚‡ˆé}Ž0Lä0–¯NùÃ#‹ÅÐŒ §ÏîèTÿ¡;P‹ÔoÆ»¥rþOI † Ä&2àY¢„iØ¿!’¥Ô S`l1ø ÕÌÇÃ	œ¤ä`ÞTƒòâ×°FÃõî¡ÈŒ FD¸ýP™ ~†ËèPeK2ã°¸ÈINŽÙÌ%¹%hæ´Ì'Ñy”ä$‹Ar‘4/]*Gû³(¨”ñÃÌ„„ÊÅþØb«©ýº]Õí²s÷â»ïÁÅÇbÞã§Ï(-Ø?Z\úÒ—¾ô¥C8*^E‹¦ÑÑ2¤=~vuÿ	cÿ®ÛÕ=yúütïªõ°Á?}ÞDbTµ»Üd]ŒÍbø—Y9§ö ö¼°ÿD®©‰–“ç·13xUX,wãy(çX›(6Ê[Å‹óŠuî—ãÛ¼ßTûNf©§÷ùTÅ¥J\k\vïÝð¸æbC¾Uò«ÚÕž<}öü–ÎÁ½f]…Çn-£ã»øé@b”H d&W#¨ª Õ¸ìRœ)a:Ð(Hø{ :è;ÆNT$Úè`êåÌÊq…ýçÛD7!ñà­J’â2f„ä¾“b)'«Nd×Œa!ùeåü¥ëÃwÆºÍÀ/oúìùmfF_óbõ³u»ÿ˜rªgQe9y~Á:ˆ¨â,qdÀšfY…˜	°âov‹•	ÚD´HÔ‚	P–ðWb²«{C­ãr€Ü®¼7|ôä6
ºÍ.áE‰Ý¹ûÅ‹£OXÀ—¡¶¨—Ñ¨ÆQÁ®RU´MÓ§#E×£§8z}%–³ç·8}í;wïÝpõÃGŸ<}öü—:­}çî½û…²'²‡?yúìù- íÚwîÞ»ÿàê‡?yúìù-ví;wïÝö‰ê9ÍÉU‰ÝÅ°Ž×ÂHñU?yþˆÛ16ÍÍOk3]·ïÜ»õ-L”¸æGÇj»u_£c« 0ŒRVñ4°«
,åAìMû—U8Ôl£Ç·y)Ë?¹Ã»ê&OŸßÇ±ÆÞÃÉèëð1x“;8×x]àTÝôùU¸ìz‰64WÅËn5æ[Ç]ýèÉà)ÈO™â^Çe7¾Q@ðMžÝÂÓtx™ãhñ®1>f!>aç__CÊîý«@T?‹²O¥”€:$°f }9¿WxP*Ø³G R"¨é êîý'Ïž_%Èè=~z‡‚Ðoý«Ÿ_Eˆy´î£'OŸß't7||UR‡ëŠ65z·Àb01µèùÕÄB\+Á½ìƒ­Ú
ÃþÀ^ä}é¢º£}´~9¸úá£ÇOž>+<(*<ûÎ]…ÇDù–“§Ïžß"µNkß¹{ïþƒ«>zü¤€óDöüøwí;wïÝpõÃG«'²§Ïžß"m×¾s÷ÞýW?|ôøÉÓgÏo‘¾kßyðèñ“gÏiNÔqÏžß"ÃÉ¬YFï>†P’m”²aÝ:ÅÑGË¦­ --[°¶žÀo §âš½ÐÄz@€ª„«j9»K¡`ƒ¢¸ján*€¶˜‘áÙ²ÿàê‡?yúìù-xk±öûW?|LíðUÉ¸*ã—ã[…ž'Z‹‰)ÇÏn3í‹(:=ÚyÏžßb¶ëÜ½›¹ÙøÔÁ\ôü ÞðÑã'OŸßB ›ÞaaôMï ê€6Ýz÷\ýðñã»%¹«BP·Î½û4°xûh—Êé-„ª­
áªëÜ»5DDÁ_…èYe»÷î‹@b7~úé\5·³Ñ±«Úóì«ÜYemNµØôÙUè˜vƒGO¯ ‡ÃŽ;T‚6\iEÐ·„“wæÀBø<#Qaš¸M5ôAy@Ì`<~rèLd¥ÅuºÏx@M RÅÏ‰F@ÒÅXÍâ‚N®féAì,XwÉ$N•BahÆ­§²„f¥ê9¯†ÿÉ9X‰Kå…²Ö
E\8T)ÏÔÕ2eE‰ËÁFK’ãôH€¡ u¨‚jãÖðnÄ58QÔ"©çÜplÑÒZ!z&(i¹ë‰mˆA‹¥ž¨”µXè‰l¶§UëIh¼¨Å-CTµÒz¸PÓS$êb®EÃÚŽ£67ÅŸ·Fh#©1CS‹´žCÃ²áßHhä4ÆhiÙêqh„7R# ˆGzÎ-nÄ42Ü@ÕJé97~M@#¶å	ÚbTìâFd#±IùcØâÎÆÅ,
m–#"¹€C >±âÇø³•$z yMð’å"`šO’>ÉJfŠ”ÌTJfŠœÌTZfêÉL9ÈL9ÊLÑ¢žFÆ²ÙHò¡?ä&K"5OÖcÉd=V(ë±$³™æ”ËÓ‰S._WQ&,¶ã>òSúÐ:;rÇ%®¡	ïà§i†Ž–³q8xNðœ2ä9…z2˜°õ£ÃI}:añ·ùOÀ’jÅ”àq›à) Ÿ/J¬Ë(ŠÑD:©½&YôáÂ4p±ãÌ•pƒÉqþ78­„ 4ƒH ™É‹X2ÅiàšùoQ+Ã
<3]!¤vØ<ä‘Œ”%„Hä<Ôˆ3–=´ Ø2»ñ g…=+˜w5òA¤ /%N ‰Ékø?š’—áD*j