<?php
/**
 *
 * electrothemecategoriessidebar.php
 *
 *  ModelExtensionModuleElectrothemecategoriessidebar
 *
 *  Model file for a front page.
 *
 */
class ModelExtensionModuleElectrothemecategoriessidebar extends Model
{


    /**
     *
     * getCategories()
     *
     * @param   $category_id    root category's id
     *
     * @return  $result         one-dimensional array of categories data
     *
     */
    public function getCategories($category_id){

        $this->load->model("catalog/category");

        $categories=$this->model_catalog_category->getCategories($category_id);
        
        $result=array();

        $this->load->model("catalog/product");

        foreach($categories as $cat){
            /*
            $amount=$this->model_catalog_product->getTotalProducts(array(
                'filter_category_id'=>$cat['category_id']
            ));
            */
            $result[]=array(
                'name'=>$cat['name'],
                'id'=>$cat['category_id'],
                //'amount'=>$amount
            );
        }

        return $result;

    }


    /**
     *
     * getPrices()
     *
     * @param   $category_id        root category's id
     *
     * @return  $result             hash of minimal and maximal prices
     *
     */
    public function getPrices($category_id,$currency=null){

        $this->load->model("catalog/product");
        $products=$this->model_catalog_product->getProducts(
            array(
                'filter_category_id'=>$category_id
            )
        );
        $i=0;
        $min_value=0;
        $max_value=0;

        foreach ($products as $product){
            $price=$this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));

            if ($i == 0){
                $min_value=$price;
                $max_value=$price;
            }

            if (isset($product['special'])){
                $special=$this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax'));
                
                $min=min($price,$special);
                $min_value=($min<$min_value)?$min:$min_value;

                $max=max($price,$special);
                $max_value=($max>$max_value)?$max:$max_value;
            } else {
                $min_value=($price<$min_value)?$price:$min_value;
                $max_value=($price>$max_value)?$price:$max_value;
            }
            $i++;
        
        }
        if (isset($currency)){
            $multiplexor=$this->getMultiplexor($currency);
        } else {
            $multiplexor=1;
        }
        
        $min_value*=$multiplexor;
        $max_value*=$multiplexor;
        $result=array(
            'min'=>floor($min_value),
            'max'=>ceil($max_value)
        );
        return $result;

    }


    /**
     *
     * getBrands()
     *
     * @param   $category_id    root category's id
     *
     * @return  $result         array
     */
    public function getBrands($category_id){

        $this->load->model("catalog/manufacturer");

        $result=array();

        $brand_list=$this->model_catalog_manufacturer->getManufacturers(array());

        $this->load->model("catalog/product");

        $products_in_category=$this->model_catalog_product->getProducts(
            array(
                'filter_category_id'=>$category_id
            )
        );
        $products_brands_ids=array();

        foreach ($products_in_category as $pic){
            $products_brands_ids[]=$pic['manufacturer_id'];
        }

        foreach ($brand_list as $brand){
            /*
            $amount=$this->model_catalog_product->getTotalProducts(array(
                'filter_manufacturer_id'=>$brand['manufacturer_id']
            ));
            */
            if (in_array($brand['manufacturer_id'],$products_brands_ids)){
                $result[]=array(
                    'name'=>$brand['name'],
                    'id'=>$brand['manufacturer_id'],
                    //'amount'=>$amount
                );
            }
        }

        return $result;

    }

    private function getMultiplexor($currency=null){
        if (!is_null($currency)){
            $temp=$this->db->query("SELECT * FROM ".DB_PREFIX."currency WHERE code='".$currency."'");
            $multiplexor=$temp->row['value'];
            if (!isset($multiplexor) || !is_numeric($multiplexor) || ($multiplexor <= 0)){
                $multiplexor=1;
            }
        
        } else {
            $multiplexor=1;
        }
        return $multiplexor;
    }
    /**
     *
     * getTopSellings()
     *
     * @param   null
     *
     * @return  <array> list of products
     *
     */
    public function getTopSellings($currency){

        $this->load->model("catalog/product");
        $products=$this->model_catalog_product->getBestSellerProducts(3);
        $result=[];
        $this->multiplexor=$this->getMultiplexor($currency);
        foreach ($products as $product){
            $r=[];
            if (!is_null($currency)){
                $r['price']=$this->currency->format($product['price'],$currency);
            } else {
                $r['price']=$product['price'];
            }
            

            if ($product['special']){
                if (!is_null($currency)){
                    $r['special']=$this->currency->format($product['special'],$currency);
                } else {
                    $r['special']=$product['special'];
                }
                
            }
            

            $r['image']=$product['image'];
            $r['name']=$product['name'];
            $r['product_id']=$product['product_id'];


            $result[]=$r;
        }
        return $result;

    }

    public function getProducts($currency,$current_category,$brands,$categories,$prices=[],$page=1,$products_per_page=15){

        $this->load->model("catalog/product");
        $this->load->model('tool/image');

        
        $this->multiplexor=$this->getMultiplexor($currency);
        $this->cur=$currency;
        $data=array(
            'amount'=>0,
            'products'=>array(),
            'page'=>$page,
            'pages'=>0
        );

        // gets all products for the current category
        $products=$this->model_catalog_product->getProducts(
            array(
                'filter_category_id'=>$current_category
            )
        );

        // gets amount of unfiltered products for selected category
        $data['amount']=count($products);

        // filterProduct function
        $filterProduct= function(&$product,$prices,&$data){
            $price=$this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax'));
            if ($prices['min']<= $this->multiplexor * $price && $prices['max']>= $this->multiplexor * $price){
                if (!in_array($product,$data['products'])){

                    if ($product['image']){
                        $product['image']=$this->model_tool_image->resize($product['image'], "228", "228");

                    } else {
                        $product['image']=$this->model_tool_image->resize("placeholder.png", "228","228");
                    }
                    $product['href']="/index.php?route=product/product&product_id=".$product['product_id'];

                    $product['price']=$this->currency->format($this->tax->calculate($product['price'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

                    
                    if ($product['special']){
                        
                        $product['special'] = $this->currency->format($this->tax->calculate($product['special'], $product['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

                    }
                    
                    $data['products'][]=$product;
                }

            } 
        };

        // loop for products to filter by brand and category
        $i=0;
        foreach ($products as $product){
            // gets the product's brand ID
            $brand=$product['manufacturer_id'];
            // gets the product's category ID
            $category_list=$this->model_catalog_product->getCategories($product['product_id']);


            if (
                count($brands) == 0
                && count($categories) == 0
            ){

                $filterProduct($product,$prices,$data);
                // $data['products'][]=$product;

            } else if (
                count($brands) > 0
                && count($categories) == 0
            ){
                if (in_array($brand,$brands)){

                    $filterProduct($product,$prices,$data);

                }
            } else if (
                count($brands) == 0
                && count($categories) > 0
            ){

                foreach ($category_list as $category){
                    if (in_array($category['category_id'],$categories) ){

                        $filterProduct($product,$prices,$data);
                    }
                }

            } else if (
                count($brands) > 0
                && count($categories) > 0
            ){

                if (in_array($brand,$brands)){
                    foreach ($category_list as $category){
                        if (in_array($category['category_id'],$categories) ){

                            $filterProduct($product,$prices,$data);

                        }
                    }
                }

            }
            $i++;
        }
        if (is_numeric($products_per_page) && $products_per_page >0){
          $data['pages']=floor(count($data['products'])/$products_per_page);
        } else {
          $data['pages']=floor(count($data['products'])/15);
        }


        return $data;
    }

}
