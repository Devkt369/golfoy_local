# migrate-click-and-collect
Migrate Store Pickup to Click & Collect

## Migrate Store Pickup to Click & Collect
1. Install module Click & Collect
2. Install `ClickAndCollectMigrate` module to Magento Website
    - Copy source code to `app/code/Magestore/ClickAndCollectMigrate`
    - Run `php bin/magento setup:upgrade` to install ClickAndCollectMigrate module
3. Create mapping data between Store Pickup's Store and POS's Location (When using `manual` option)
    - Insert mapping data to `webpos_location_storepickup_store_map` table
    - Let `location_id` = NULL if there isn't any location mapping with store and the tool will automatically create new location
    - If you don't want to migrate a store, don't insert it to this table
4. Run `php bin/magento cache:disable` to disable cache
5. Run `clickandcollect:migrate-storepickup` to migrate data
    - Mapping between Store And Location
        - Add option `-m` if you manually created the mapping data between stores and locations
        - If you don't use option `-m`, the tool will automatically detect the mapping data between stores and locations
            - Formula: store -> source -> stock link to source with 1:1 relationship -> location
            - If don't have any suitable stock then the tool will ignore that store
            - If there is suitable stock but don't have any suitable location then let `location_id` value is NULL
    - Check for upmapped stores
        - By default, the tool will check for unmapped stores. If there are stores that haven't add to the map table, it will show error and stop migrate.
        - If you make sure that you want to ignore those stores, you can add option `-i` to the command.
            > Note: The orders that were created on those stores will not be migrated
    - Migrate data
        - If there is no mapping data in the `webpos_location_storepickup_store_map` table, show warning message and stop migrate
        - Migrate data in below order
            - Schedule
            - Location
            - Special Day
            - Order
6. (Optional) Run `php bin/magento cache:enable` to enable cache
7. Check data to make sure all the data was migrated correctly
7. Disable module `ClickAndCollectMigrate`
8. Disable modle `Store Pickup`
