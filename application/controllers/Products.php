<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Products extends REST_Controller
{

    private $allowed_img_types;

    function __construct()
    {
        parent::__construct();
        $this->methods['all_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['one_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['set_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['productDel_delete']['limit'] = 50; // 50 requests per hour per user/key
        $this->load->model(array('Api_model', 'admin/Products_model'));
        $this->allowed_img_types = $this->config->item('allowed_img_types');
    }

    /*
     * Get All Products
     */
public function productList_get()
 {
     $query=$this->db->query("select vendors.url as vendor_url, products.id,products.image, products.quantity, products_translations.title, products_translations.price, products_translations.old_price, products.url
     from products
             inner join products_translations  on products_translations.for_id = products.id
             inner join vendors on vendors.id = products.vendor_id
             
             where(products_translations.abbr= 'en' and visibility= 1)");
$result=$query->result();
$res=array(
    'error'=>false,
    'msg'=> $result
);
$result = $this->response($res, REST_Controller::HTTP_OK);

//$this->response($res, REST_Controller::HTTP_OK);
return $result;

 }   
 public function all_get($lang)
    {
       // $products = $this->Api_model->getProducts($lang);
       $query=$this->db->query("select vendors.url as vendor_url, products.id,products.image, products.quantity, products_translations.title, products_translations.price, products_translations.old_price, products.url
       from products
               inner join products_translations  on products_translations.for_id = products.id
               inner join vendors on vendors.id = products.vendor_id
               
               where(products_translations.abbr= 'en' and visibility= 1)
               ");
$products=$query->result_array();
        // Check if the products data store contains products (in case the database result returns NULL)
        if ($products) {
            // Set the response and exit
            $this->response($products, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No products were found'
                    ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    /*
     * Get One Product
     */

    public function one_get($lang, $id)
    {
        $product = $this->Api_model->getProduct($lang, $id);

        // Check if the products data store contains products (in case the database result returns NULL)
        if ($product) {
            // Set the response and exit
            $this->response($product, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No product were found'
                    ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    /*
     * Set Product
     */

    public function set_post()
    {
        $errors = [];
        $_POST['image'] = $this->uploadImage();
        if (!isset($_POST['translations']) || empty($_POST['translations'])) {
            $errors[] = 'No translations array or empty';
        }
        if (!isset($_POST['title']) || empty($_POST['title'])) {
            $errors[] = 'No title array or empty';
        }
        if (!isset($_POST['basic_description']) || empty($_POST['basic_description'])) {
            $errors[] = 'No basic_description array or empty';
        }
        if (!isset($_POST['description']) || empty($_POST['description'])) {
            $errors[] = 'No description array or empty';
        }
        if (!isset($_POST['price']) || empty($_POST['price'])) {
            $errors[] = 'No price array or empty';
        }
        if (!isset($_POST['old_price']) || empty($_POST['old_price'])) {
            $errors[] = 'No old_price array or empty';
        }
        if (!isset($_POST['shop_categorie'])) {
            $errors[] = 'shop_categorie not found';
        }
        if (!isset($_POST['quantity'])) {
            $errors[] = 'quantity not found';
        }
        if (!isset($_POST['in_slider'])) {
            $errors[] = 'in_slider not found';
        }
        if (!isset($_POST['position'])) {
            $errors[] = 'position not found';
        }
        if (!empty($errors)) {
            $error = implode(", ", $errors);
            $message = [
                'message' => $error
            ];
        } else {
            $this->Api_model->setProduct($_POST);
            $message = [
                'message' => 'Added a resource'
            ];
        }
        $this->set_response($message, REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
    }

    private function uploadImage()
    {
        $config['upload_path'] = './attachments/shop_images/';
        $config['allowed_types'] = $this->allowed_img_types;
        $this->load->library('upload', $config);
        $this->upload->initialize($config);
        if (!$this->upload->do_upload('userfile')) {
            log_message('error', 'Image Upload Error: ' . $this->upload->display_errors());
        }
        $img = $this->upload->data();
        return $img['file_name'];
    }

    public function productDel_delete($id)
    {
        $id = (int) $id;
        // Validate the id.
        if ($id <= 0) {
            // Set the response and exit
            $this->response(NULL, REST_Controller::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }
        $this->Api_model->deleteProduct($id);
        $message = [
            'id' => $id,
            'message' => 'Deleted the resource'
        ];
        $this->set_response($message, REST_Controller::HTTP_NO_CONTENT); // NO_CONTENT (204) being the HTTP response code
    }
    

    public function commander_get($id){
      //  $id=$this->input->post('id');
        $this->db->join('vendors', 'vendors.id = products.vendor_id', 'left');
        $this->db->join('products_translations', 'products_translations.for_id = products.id', 'left');
        $this->db->where('products.id', $id);
        $query = $this->db->select(' products.id as product_id, products.image as product_image, products.time as product_time_created, products.time_update as product_time_updated, products.visibility as product_visibility, products.shop_categorie as product_category, products.quantity as product_quantity_available, products.procurement as product_procurement, products.url as product_url, products.virtual_products, products.brand_id as product_brand_id, products.position as product_position , products_translations.title, products_translations.description, products_translations.price, products_translations.old_price, products_translations.basic_description')->get('products');
       $result=  $query->result_array();
        $res=array(
            'error'=>false,
            'msg'=> $result
        );
        $result = $this->response($res, REST_Controller::HTTP_OK);
        
        //$this->response($res, REST_Controller::HTTP_OK);
        return  $result;
        

    }
    public function userInfoValidate_post()
    {$name=$this->input->post('first_name');
        $prenom=$this->input->post('last_name');
        $adresse=$this->input->post('address');
        $ville=$this->input->post('city');
        $téléphone=$this->input->post('phone');
        $email=$this->input->post('email');
        $data=array(
            'first_name'=>$name,
            'last_name' =>$prenom,
            'address' =>$adresse,
            'city' =>$ville,
            'phone'=>$téléphone,
            'email'=>$email            
        );
        $this->Api_model->insertOrder($data);   
        
    }
    public function allproducts_get()
    {//$lang="en";
       // $products = $this->Api_model->getProducts($lang);
       $query=$this->db->query("select products.quantity,products_translations.description, products.id,products.image, products.quantity as amount, products_translations.title, products_translations.price, products_translations.old_price, products.url
       
       from products
               inner join products_translations  on products_translations.for_id = products.id
               
               
               where(products_translations.abbr= 'en' and visibility= 1)
               ");
$products=$query->result_array();
        // Check if the products data store contains products (in case the database result returns NULL)
        if ($products) {
            // Set the response and exit
            $this->response($products, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No products were found'
                    ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

 

}
