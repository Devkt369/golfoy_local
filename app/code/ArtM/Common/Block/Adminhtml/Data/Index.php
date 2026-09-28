<?php

namespace ArtM\Common\Block\Adminhtml\Data;

use Magento\Backend\Block\Template;

class Index extends Template
{
    // top-heading
    public function getTopHeadingHtml()
    {
        // Adjust the HTML structure based on your requirements
        return '
        <div class="row">
            <div class="col-sm-12 main_header">
            <h2>golf shoes</h2>
            </div>
        </div>
        ';
    }
    // Slider Form
    public function getSliderHtml()
    {
        return '
        <div class="row shoes_blocks shoes_full_block slider_landing_page slider-block">
        <div id="golfoy_slider" class="carousel slide" data-ride="carousel" data-interval="5000">
            <div class="carousel-inner home_slider_pc">
                <div class="col-xs-12 block_col item pc_banner_home active"><a href="#"><img class="web hidden-xs img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/Puma-Hero-Slider.jpg" alt="Puma" loading="lazy"><img class="web visible-xs img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/Puma-Shoe-Mobile-Slider.jpg" alt="puma" loading="lazy"></a>
                </div>
                <div class="col-xs-12 block_col item pc_banner_home"><a href="#"><img class="web hidden-xs img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/Ecco-Hero-Slider.jpg" alt="ecco" loading="lazy"><img class="web visible-xs img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/Ecco-Shoe-Mobile-Slider.jpg" alt="ecco" loading="lazy"></a>
                </div>
            </div>
            <!-- Add carousel indicators -->
            <ol class="carousel-indicators slider-carousel">
                <li class="active" data-target="#golfoy_slider" data-slide-to="0">&nbsp;</li>
                <li data-target="#golfoy_slider" data-slide-to="1">&nbsp;</li>
                <!-- Add more li elements for additional slides -->
            </ol>
        </div>
    </div>
    ';
    }
    // Heading Above Block Form center
    public function getHeadingBlockHtml()
    {
        return '
    <h2 class="center_heading">By Gender</h2>
    ';
    }
    // Eight Block Form 
    public function getEightBlockHtml()
    {
        return '
    <div id="eight_block">
    <div class="row eight_block">
    <div class="col-sm-3 col-sm-3 col-xs-3"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/UK-5.png" alt="uk" loading="lazy"></a></div>
    <div class="col-sm-3  col-sm-3 col-xs-3"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/UK-6.png" alt="Women shoes" loading="lazy"></a></div>
    <div class="col-sm-3  col-sm-3 col-xs-3"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/UK-7.png" alt="Junior shoes" loading="lazy"></a></div>
    <div class="col-sm-3  col-sm-3 col-xs-3"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/UK-8.png" alt="View all collection" loading="lazy"></a></div>
    <div class="col-sm-3  col-sm-3 col-xs-3"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/UK-9.png" alt="View all collection" loading="lazy"></a></div>
    <div class="col-sm-3  col-sm-3 col-xs-3"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/UK-10.png" alt="View all collection" loading="lazy"></a></div>
    <div class="col-sm-3  col-sm-3 col-xs-3"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/UK-11.png" alt="View all collection" loading="lazy"></a></div>
    <div class="col-sm-3  col-sm-3 col-xs-3"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/UK-12.png" alt="View all collection" loading="lazy"></a></div>
    </div></div>
    ';
    }
    // Six Block Form
    public function getSixBlockHtml()
    {
        return '
        <div id="six_block_web" class="hidden-xs-web">
        <div class="row six_block_web">
        <div class="col-sm-2 col-m-2"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/six_width/footjoy Traditions-25.jpg" alt="footjoy" loading="lazy"> </a></div>
        <div class="col-sm-2 col-m-2"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/six_width/adidas bounce 3.0-25.jpg" alt="addidas" loading="lazy"> </a></div>
        <div class="col-sm-2 col-m-2"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/six_width/Skechers Drive 5-25.jpg" alt="sketcher" loading="lazy"> </a></div>
        <div class="col-sm-2 col-m-2"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/six_width/puma Fusion Pro-25.jpg" alt="puma-drive" loading="lazy"> </a></div>
        <div class="col-sm-2 col-m-2"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/six_width/Mizuno Nexlite-25.jpg" alt="mizuno" loading="lazy"> </a></div>
        <div class="col-sm-2 col-m-2"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/six_width/footjoy Traditions-25.jpg" alt="shoe-care" loading="lazy"> </a></div>
        </div>
        </div>
        ';
    }
    //for mobile
    public function getSixBlockMobileHtml()
    {
        return '
        <div id="six_block_mobile" class="visibile-xs-mobile">
        <div class="row six_block_mobile">
            <div class="row six-div-mbl">
            <div class="col-xs-6"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/six_width/footjoy-Traditions-21.jpg" alt="footjoy" loading="lazy"> </a></div>
            <div class="col-xs-6"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/six_width/adidas-bounce 3.0-21.jpg" alt="addidas" loading="lazy"> </a></div>
            </div>
            <div class="row six-div-mbl">
            <div class="col-xs-6"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/six_width/Skechers Drive 5-25.jpg" alt="sketcher" loading="lazy"> </a></div>
            <div class="col-xs-6"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/six_width/puma-Fusion-Pro-21.jpg" alt="puma" loading="lazy"> </a></div>
            </div>
            <div class="row six-div-mbl">
            <div class="col-xs-6"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/six_width/Mizuno-Nexlite-21.jpg" alt="pumma-men" loading="lazy"> </a></div>
            <div class="col-xs-6"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/six_width/shoe care-25.jpg" alt="shoe-care" loading="lazy"> </a></div>
            </div>
        </div>
        </div>

    ';
    }
    // Four Block Form
    public function getFourBlockHtml()
    {
        return '
        <div id="four_block">
        <div class="row four_block">
        <div class="col-sm-3 col-xs-6"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/viper golf.jpg" alt="viper" loading="lazy"> </a></div>
        <div class="col-sm-3 col-xs-6"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/Oakley.jpg" alt="oclay" loading="lazy"> </a></div>
        <div class="col-sm-3 col-xs-6"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/Winter caps.jpg" alt="winter" loading="lazy"> </a></div>
        <div class="col-sm-3 col-xs-6"><a href="#"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/Adidas Tshirts.jpg" alt="addidas" loading="lazy"> </a></div>
        </div></div>
    ';
    }
    // Three Block Column in One Row Form 
    public function getThreehoverHtml()
    {
        return '
        <div class="row three_block_shoes_outer three_column hover-block">
        <div class="col-sm-12 background-full-width"><img class="img-fluid hidden-xs" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/Rectangle-button.jpg" alt="Rectangle Button.png"><img class="img-fluid visible-xs" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/Rectangle-button-mobile.jpg" alt="Rectangle Button.png">
        <div class="shoes_blocks shoes_three_block three_block_sales">
            <div class="col-sm-4 col-xs-4 shoes_three_div_blocks bst"><a href="#"><img class="img-fluid hidden-xs" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/Best-Sellers.png" alt="Best Sellers"><img class="img-fluid visible-xs" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/Best -Sellers-mobile.png" alt="Best Sellers"></a></div>            
            <div class="col-sm-4 col-xs-4 shoes_three_div_blocks sale"><a href="#"><img class="img-fluid hidden-xs"src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/SALE.png" alt="SALE"><img class="img-fluid visible-xs"src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/SALE-mobile.png" alt="SALE"></a></div>
            <div class="col-sm-4 col-xs-4 shoes_three_div_blocks under"><a href="#"><img class="img-fluid hidden-xs" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/Under -11995.png" alt="Under ₹ ₹ 11,995"><img class="img-fluid visible-xs" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/shoes/Under-11,995-mobile.png" alt="Under ₹ ₹ 11,995"></a></div>
        </div>
        </div>
    </div>
    ';
    }
    // Three Block Form
    public function getThreeBlockHtml()
    {
        return '
    <div id="three_block">
    <div class="row three_block">
        <div class="col-sm-4 col-xs-12"><a href="#"><img src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/three_collout/tour ball-new-22.jpg" alt="tour-golf"></a></div>
        <div class="col-sm-4 col-xs-12"><a href="#"><img src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/three_collout/distance-mobile.jpg" alt="distance"></a></div>
        <div class="col-sm-4 col-xs-12"><a href="#"><img src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/three_collout/visiable balls-new-22.jpg" alt="visbile"></a></div>
    </div></div>
    ';
    }
    // Two Block Form
    public function getTwoBlockHtml()
    {
        return '
        <div id="two_block">
        <div class="row two_block">
        <div class="col-sm-6 col-m-6 col-xs-12"><a href="#"><img  src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/two_width/starter-pack-img.jpg" alt="2-block-callout-afasd" loading="lazy"> </a></div>
        <div class="col-sm-6 col-m-6 col-xs-12"><a href="#"><img src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/two_width/taylormade-full-set.jpg" alt="taylormade-full-set" loading="lazy"> </a></div>
        </div></div>
    ';
    }
    // Full Width Block Form
    public function getFullBlockHtml()
    {
        return '
    <div id="full_block" class="hidden-xs-web">    
    <div class="row full_block_web">
    <div class="col-xs-12"><a href="#"><img class="web" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/full_width/FOOTJOY.jpg" alt="footjoy" loading="lazy"> </a></div>
    </div></div>
    ';
    }
    // FOR MOBILE 
    public function getFullBlockMobileHtml()
    {
        return '
    <div id="full_block_mobile" class="visibile-xs-mobile">
    <div class="row full_block_mobile">
    <div class="col-xs-12"><a href="#"><img class="mobile" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/full_width/FOOTJOY-mobile.jpg" alt="footjoy" loading="lazy"> </a></div>
    </div></div>
    ';
    }
    //product slider
    public function getBrandslove()
    {
        return '
        <div class="row brands_on_sale title_block">
        <div class="col-xs-12">
        <div class="heading_golfoy_left">
        <h2>Brand You’ll Love</h2>
        </div>
        </div>
        <div class="col-xs-12 animated-div-block">
        <div class="row b_o_s">
        <div class="block_col col-md-2 col-sm-4 col-xs-6 slide pa_re">
        <div class="img_1"><a href="#"><img class="image" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/Layer_7_2.png" alt="callaway"></a></div>
        <div class="overlay"><a href="#"><img class="text" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/bottom-30-off.png" alt="callaway"></a></div>
        </div>
        <div class="block_col col-md-2 col-sm-4 col-xs-6 slide pa_re">
        <div class="img_1"><a href="#"><img class="image" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/Layer_7_1.png" alt="Taylormade"></a></div>
        <div class="overlay"><a href="#"><img class="text" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/bottom-41-off.png" alt="Taylormade"></a></div>
        </div>
        <div class="block_col col-md-2 col-sm-4 col-xs-6 slide pa_re">
        <div class="img_1"><a href="#"><img class="image" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/fj_top_15_new.png" alt="Footjoy_15"></a></div>
        <div class="overlay"><a href="#"><img class="text" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/Callaway_15.png" alt="Footjoy_15"></a></div>
        </div>
        <div class="block_col col-md-2 col-sm-4 col-xs-6 slide pa_re">
        <div class="img_1"><a href="#"><img class="image" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/top.png" alt="vipergolf"></a></div>
        <div class="overlay"><a href="#"><img class="text" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/Vipergolf_15.png" alt="vipergolf"></a></div>
        </div>
        <div class="block_col col-md-2 col-sm-4 col-xs-6 slide pa_re">
        <div class="img_1"><a href="#"><img class="image" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/Layer_7_5.png" alt="adidas"></a></div>
        <div class="overlay"><a href="#"><img class="text" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/Adidas_15.png" alt="adidas"></a></div>
        </div>
        <div class="block_col col-md-2 col-sm-4 col-xs-6 slide pa_re">
        <div class="img_1"><a href="#"><img class="image" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/titleist_top_15_new.png" alt="titleist"></a></div>
        <div class="overlay"><a href="#"><img class="text" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2023_homepage/four_collout/titllest_offer.png" alt="Titleist"></a></div>
        </div>
        </div>
        </div>
        </div>
    ';
    }
    // Heading Above Block Form left
    public function getHeadingBlockLeftHtml()
    {
        return '
        <div class="col-xs-12 header-left">
        <div class="heading_golfoy_left">
        <h2>shoes collections</h2>
        </div>
        </div>
     ';
    }

    //four block two block
    public function getFourBlockIntoTwoBlock()
    {
        return '
            <div class="row">
            <div class="col-sm-6" style="padding: 0px 0px 30px 0px;">
            <div class="col-xs-12 header-left">
            <div class="heading_golfoy_left">
            <h2>Women</h2>
            </div>
            </div>
            <div id="four_block" style="padding: 20px 0px 0px 0px;">
            <div class="row four_block">
            <div class="col-sm-6 col-xs-6"><a href="https://staging-golfoy.hyperx.cloud/women-s/by-type/apparels.html?cat=752"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2024/landing/four_width/T-Women-4-Block-Mobile.jpg" alt="Taylormade-RBZ-SpeedLite" loading="lazy"> </a></div>
            <div class="col-sm-6 col-xs-6"><a href="https://staging-golfoy.hyperx.cloud/women-s/by-type/clubs.html"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2024/landing/four_width/c-Women-4-Block-Mobile.jpg" alt="Callaway-Warbird" loading="lazy"> </a></div>
            <div class="col-sm-6 col-xs-6" style="padding-top: 20px"><a href="https://staging-golfoy.hyperx.cloud/women-s/by-type/shoes.html"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2024/landing/four_width/W-Shoes-Women-4-Block-Mobile.jpg" alt="Cobra-Air-X2" loading="lazy"> </a></div>
            <div class="col-sm-6 col-xs-6" style="padding-top: 20px"><a href="https://staging-golfoy.hyperx.cloud/women-s/by-type/gloves.html"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2024/landing/four_width/G-Women-4-Block-Mobile.jpg" alt="Wilson-1200-Set" loading="lazy"> </a></div>
            </div>
            </div>
            </div>
            
            <div class="col-sm-6" style="padding: 0px 0px 30px 0px;">
            <div class="col-xs-12 header-left">
            <div class="heading_golfoy_left">
            <h2>junior</h2>
            </div>
            </div>
            <div id="four_block" style="padding: 20px 0px 0px 0px;">
            <div class="row four_block">
            <div class="col-sm-6 col-xs-6"><a href="https://staging-golfoy.hyperx.cloud/junior-section/by-type/shoes.html"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2024/landing/four_width/J-Shoes-Junior-4-Block-Mobile.jpg" alt="Taylormade-RBZ-SpeedLite" loading="lazy"> </a></div>
            <div class="col-sm-6 col-xs-6"><a href="https://staging-golfoy.hyperx.cloud/junior-section/by-type/package-sets.html"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2024/landing/four_width/P-Junior-4-Block-Mobile.jpg" alt="Callaway-Warbird" loading="lazy"> </a></div>
            <div class="col-sm-6 col-xs-6" style="padding-top: 20px"><a href="https://staging-golfoy.hyperx.cloud/junior-section/by-type/glove.html"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2024/landing/four_width/G-Junior-4-Block-Mobile.jpg" alt="Cobra-Air-X2" loading="lazy"> </a></div>
            <div class="col-sm-6 col-xs-6" style="padding-top: 20px"><a href="https://staging-golfoy.hyperx.cloud/junior-section/by-type/individual-club.html"><img class="img-fluid" src="https://staging-golfoy.hyperx.cloud/media/wysiwyg/2024/landing/four_width/I-Junior-4-Block-Mobile.jpg" alt="Wilson-1200-Set" loading="lazy"> </a></div>
            </div>
            </div>
            </div>
            </div>
        ';
    }
}
