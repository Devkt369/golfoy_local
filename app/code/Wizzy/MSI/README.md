# Wizzy_MSI module for Magento2

Wizzy_MSI is a Magento 2 module designed to manage multi-inventory sources and stocks, providing enhanced inventory management capabilities to your Magento store. With Wizzy_MSI, you can efficiently handle stock quantities, availability across multiple warehouse locations.

# Short Discription for the Module

The Wizzy_MSI module is designed to retrieve the list of Inventory sources available in your store and fetch the corresponding product stock data for each source.

To configure the module, follow these steps:

1. Navigate to your Magento 2 admin panel.
2. Go to Stores -> Wizzy Search -> Catalogue Index -> Inventory Configuration.
3. Enable the option "Do you use Sources for Inventory Management?"
4. From the provided list, select the desired Source of Inventory.

The module will then consider the selected source to determine the stock status and stock quantity of products.

# Instllation

1. Download the module from [here](https://github.com/wizzy-ai/wizzy-search-msi).

2. Paste the MSI folder from wizzy-search-msi/Wizzy/ Inside the app/code/Wizzy directory.

3. Run the following commands from the root directory of your Magento 2 
 
- php bin/magento module:enable Wizzy_MSI
- php bin/magento setup:upgrade
- php bin/magento setup:di:compile
- php bin/magento cache:flush

## Found Bugs? Need Help? Have Questions? 
If you’ve found any bug in the module, you can [create an issue](https://github.com/wizzy-ai/wizzy-search-msi) in this Repository, and our team will look into it as quickly as possible.

You can also contact us on [team@wizzy.ai](mailto:team@wizzy.ai)