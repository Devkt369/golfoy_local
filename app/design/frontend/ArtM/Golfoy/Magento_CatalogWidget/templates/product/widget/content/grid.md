<!-- by ussing category id  -->
<p>{{widget type="Magento\CatalogWidget\Block\Product\ProductsList"
    products_per_page="8"
    products_count="8"
    template="product/widget/content/grid.phtml"
    conditions_encoded="^[`1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,`aggregator`:`all`,`value`:`1`,`new_child`:``^],`1--1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,`attribute`:`category_ids`,`operator`:`==`,`value`:`4`^]^]"
}}</p>

<!-- by ussing sku  -->
<p>
{{widget 
    type="Magento\CatalogWidget\Block\Product\ProductsList" 
    products_count="20" 
    template="product/widget/content/grid.phtml" 
    conditions_encoded="^[`1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Combine`,`aggregator`:`all`,`value`:`1`,`new_child`:``^],`1--1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,`attribute`:`sku`,`operator`:`()`,
    `value`:`10850455569, 214064-WNVB,  214064-BKGY, 19510101`^]^]"}}</p>

<!-- by ussing attribute_set_id  -->    
<p>
{{widget 
    type="Magento\CatalogWidget\Block\Product\ProductsList"
    products_count="20"
    template="product/widget/content/grid.phtml"
    conditions_encoded="^[`1`:^[`type`:`Magento||CatalogWidget||Model||Rule||Condition||Product`,
        `attribute`:`attribute_set_id`,
        `operator`:`==`,
        `value`:`15`^]^]"
}}
</p>
