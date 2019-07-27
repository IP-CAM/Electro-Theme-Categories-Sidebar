<?php
/**
 * 	electrothemecategoriessidebar.php
 * 
 * 	ControllerExtensionModuleElectrothemecategoriessidebar
 * 
 * 	Controller class for a front page.
 */
class ControllerExtensionModuleElectrothemecategoriessidebar extends Controller
{
	/**
	 * 
	 * bindModel()
	 * 
	 * @param	null
	 * 
	 * @return	null
	 * 
	 */
	private function bindModel(){
		$this->load->model('extension/module/electrothemecategoriessidebar');
		$this->load->language('extension/module/electrothemecategoriessidebar');
	}
	/**
	 * 
	 * index()
	 * 
	 */
	public function index()
	{
		$data=array();
		$this->load->language('extension/module/electrothemecategoriessidebar');
		$this->bindModel();
		$currency=$this->session->data['currency'];
		$data['currency']=$currency;

		$url=$_REQUEST['path'];
		$data['url']=$url;
		$data['top_sellings']=$this->model_extension_module_electrothemecategoriessidebar->getTopSellings($currency);

		

		return $this->load->view('extension/module/electrothemecategoriessidebar', $data);
	}
	/**
	 * 
	 * 
	 * Loads data after 'category' page was loaded.
	 * 
	 * @param	null	
	 * 
	 * @return	<json>
	 * 
	 */
	public function getdata(){
		$this->bindModel();

		if (isset($this->request->get['category'])){

			$current_category=$this->request->get['category'];
			$data['categories']=$this->model_extension_module_electrothemecategoriessidebar->getCategories($current_category);
			$data['brands']=$this->model_extension_module_electrothemecategoriessidebar->getBrands($current_category);
			if (isset($this->request->get['currency'])){
				$currency=$this->request->get['currency'];
			} else {
				$currency=null;
			}

			$data['prices']=$this->model_extension_module_electrothemecategoriessidebar->getPrices($current_category,$currency);

		}
		$data['text_categories']=$this->language->get('text_categories');
		$data['text_price']=$this->language->get('text_price');
		$data['text_brand']=$this->language->get('text_brand');
		$data['text_top_selling']=$this->language->get('text_top_selling');

		$this->response->addHeader('Content-Type: application/json');
		return $this->response->setOutput(json_encode($data));
	}
	/**
	 * 
	 * topsellings()
	 * 
	 * Loads 'Top Sellings' widget on the 'category' page.
	 * 
	 * @param	null
	 * 
	 * @return	<json>
	 * 
	 */
	public function topsellings(){
		$this->bindModel();
		$data=array(
			"products"=>array()
		);
		
			if (isset($this->request->get['currency'])){
				$currency=$this->request->get['currency'];
			} else {
				$currency=null;
			}
		
		
		$products=$this->model_extension_module_electrothemecategoriessidebar->getTopSellings($currency);
		foreach ($products as $product){
			$data['products'][] = $product;
		}

		$this->response->addHeader('Content-Type: application/json');
		return $this->response->setOutput(json_encode($data));
	}
	/**
	 * 
	 * products()
	 * 
	 * Loads the products' data on the 'category' page
	 * 
	 * @param	null
	 * 
	 * @return	<json>
	 * 
	 */
	public function products(){
		$this->bindModel();
		$data=array();

		$hasCategories=isset($this->request->get["categories"]);
		$hasBrands=isset($this->request->get["brands"]);
		
		if (
			$this->is_valid('min_price')
			){
			$min_price=$this->request->get['min_price'];
		} else {
			$min_price='0';
		}
		
		if (
			$this->is_valid('max_price')
		){
			$max_price=$this->request->get['max_price'];
		} else {
			$max_price='0';
		}

		$prices=array(
			"min" => $min_price,
			"max" => $max_price
		);

		$products_per_page=
		(
			$this->is_valid('products_per_page')
		)
		?$this->request->get['products_per_page']:'15';

		$page=
		(
			$this->is_valid('page')
		)
		?$this->request->get['page']:'1';	// if page number is not set or invalid assign '1' to the page number

		
		$brands=$hasBrands?($this->request->get["brands"]):[];
		$categories=$hasCategories?($this->request->get["categories"]):[];

		
		if (
			$this->is_valid('category')
			){
			$current_category=$this->request->get['category'];
			$currency=$this->session->data['currency'];
			$data=$this->model_extension_module_electrothemecategoriessidebar->getProducts(
				$currency,
				$current_category,
				$brands,
				$categories,
				$prices,
				$page,
				$products_per_page
			);
		} else {
			$data=array(
				'amount'=>0,
				'products'=>array(),
				'page'=>$page,
				'pages'=>0
			);
		}
		$data['text_categories']=$this->language->get('text_categories');
		$data['text_price']=$this->language->get('text_price');
		$data['text_brand']=$this->language->get('text_brand');
		$data['text_top_selling']=$this->language->get('text_top_selling');

		$data['wishlist']=$this->language->get('button_wishlist');
		$data['compare']=$this->language->get('button_compare');
		$data['addtocart']=$this->language->get('button_cart');
		
		$this->response->addHeader('Content-Type: application/json');
		return $this->response->setOutput(json_encode($data));
	}
	private function is_valid($line){
		if (isset($this->request->get[$line])){

			$var=$this->request->get[$line];

			if (
				is_numeric($var)
				//&& is_int($line) 
				&& ($var > 0)
			){
				return true;
			}else {
				return false;
			}
			
		} else {
			return false;
		}
		
	}
}