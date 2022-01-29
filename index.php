<?php

/**
 * Plugin Name: Toplu İndirim Alanı Oluştur - YEMLİHA
 * Plugin URI: https://www.yemlihakorkmaz.com/
 * Description: Ürünleri indirim oranına göre sıralamaya yarayan eklenti
 * Version: 1.0
 * Author: yemlihakorkmaz
 * Author URI: http://yemlihakorkmaz.com.com/
 **/
//Eklenti menüsü


function my_admin_menu()
{
    add_menu_page(
        __('Toplu İndirim Oranı Oluştur', 'my-textdomain'),
        __('Toplu İndirim Oranı Oluştur', 'my-textdomain'),
        'manage_options',
        'sample-page',
        'my_admin_page_contents',
        'dashicons-schedule',
        3
    );
}

add_filter('woocommerce_catalog_orderby', 'yeni_siralama_ekle');

function yeni_siralama_ekle($options)
{

    $options['discount'] = 'İndirime Göre Sırala';

    return $options;
}


add_filter('woocommerce_get_catalog_ordering_args', 'siralama_ayarlari_ekle');

function siralama_ayarlari_ekle($args)
{
    if (isset($_GET['orderby']) && 'discount' === $_GET['orderby']) {
        $args['orderby']  = 'meta_value_num';
        $args['order']    = 'DESC';
        $args['meta_key'] = '_discount_percent';
    }
    return $args;
}


// bu alan ürün düzenleme sayfasında ne kadar indirim oranı var onu göstermeye yarar
function action_woocommerce_product_options_general_product_data()
{
    // Text field
    woocommerce_wp_text_input(array(
        'id'                 => '_discount_percent',
        'label'              => __('İndirim Yüzdesi', 'woocommerce'),
        'placeholder'        => '',
        'description'        => __('İndirim Oranı Miktarını Gösterir', 'woocommerce'),
        'desc_tip'           => true,
        'custom_attributes'  => array('readonly' => true),
    ));
}
add_action('woocommerce_product_options_general_product_data', 'action_woocommerce_product_options_general_product_data', 10, 0);

//Ürün ekledikten veya güncelledikten sonra otamatik indirim oranı oluşturur
function action_woocommerce_admin_process_product_object($product)
{
    // Getters
    $regular_price = (float) $product->get_regular_price();
    $sale_price = (float) $product->get_sale_price();

    // NOT empty
    if (!empty($sale_price)) {
        // Calculate
        $discount_percent = round(100 - ($sale_price / $regular_price * 100), 4);

        // Update
        $product->update_meta_data('_discount_percent', $discount_percent);
    } else {
        $product->update_meta_data('_discount_percent', 0);
    }
}
add_action('woocommerce_admin_process_product_object', 'action_woocommerce_admin_process_product_object', 10, 1);



add_action('admin_menu', 'my_admin_menu');



function my_admin_page_contents()
{
?>



    <?php

    if ($_POST['calistir'] == '1') {
        $all_ids = get_posts(array(
            'post_type' => 'product',
            'orderby' => 'meta_value_num',
            'order' => 'ASC', // you can modify it as per your use
            'numberposts' => -1,
            'post_status' => 'publish',
            'fields' => 'ids',


        ));

        $total = 1;
        $indirsay = 1;
        foreach ($all_ids as $id) :

            $product = wc_get_product($id);
            $sale_price = (float)get_post_meta($id, '_sale_price', true);
            $regular_price = (float)get_post_meta($id, '_regular_price', true);
            $price = (float)get_post_meta($id, '_price', true);
            $discount = (float)get_post_meta($id, '_discount_percent', true);

            if ($sale_price > 0 && $regular_price > 0) {

                $indirim = round(100 - ($sale_price / $regular_price * 100), 1);
            } else {
                $indirim = 0;
            }


            if (isset($discount) && !empty($discount)) {
                if ($discount != $indirim) {
                    update_post_meta($id, '_discount_percent', $indirim);
                    $indirsay++;
                }
            } else {
                update_post_meta($id, '_discount_percent', $indirim);
                $indirsay++;
            }

            $total++;
        endforeach;
    ?>
        <div class="wpwrap">

            <div class="wpcontent">
                <h1><?php echo count($all_ids) . 'Ürün Var'; ?></h1>
                <h1> <?php echo $indirsay . 'Kayıt Güncellendi'; ?></h1>

            <?php    } ?>
            <h1>Toplu bir şekilde tüm ürünlere indirim oranı ekler</h1>
            <form action="" method="post">
                <input type="hidden" value="1" name="calistir">
                <input class="form-control" type="submit">

            </form>

            </div>
        </div>


    <?php
}
