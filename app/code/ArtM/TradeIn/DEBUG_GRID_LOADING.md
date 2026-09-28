# Magento Grid Loading Issue - Debug Steps

## Common Causes and Solutions

### 1. **Clear Cache and Check Logs**
```bash
php bin/magento cache:flush
tail -f var/log/system.log
tail -f var/log/debug.log
```

### 2. **Test Data Source URL**
Visit: `{your_admin_url}/artmtradein/index/testData`
This will show if the collection is working properly.

### 3. **Check Database Connection**
Make sure the `artm_tradein` table exists and has data:
```sql
SELECT COUNT(*) FROM artm_tradein;
SELECT * FROM artm_tradein LIMIT 5;
```

### 4. **Browser Network Tab**
1. Open browser Developer Tools (F12)
2. Go to Network tab
3. Reload the grid page
4. Look for any failed AJAX requests to `mui/index/render`
5. Check response for errors

### 5. **Module Registration Check**
```bash
php bin/magento module:status ArtM_TradeIn
```
Should show "Module is enabled"

### 6. **If Still Loading:**

#### Option A: Revert to Custom Grid Temporarily
Restore the original `grid.phtml` template by updating the layout:
```xml
<referenceContainer name="content">
    <block class="ArtM\TradeIn\Block\Adminhtml\Tradeform\Tradeform" 
           name="tradein.grid" 
           template="ArtM_TradeIn::grid.phtml"/>
</referenceContainer>
```

#### Option B: Enable Debug Mode
```bash
php bin/magento deploy:mode:set developer
```

### 7. **Current Simplified Grid Status**
The grid has been simplified to show only basic columns:
- Trade ID
- Ad ID  
- Status
- Email
- Equipment
- Brand
- Created At

Once this basic grid loads, we can add back the complex columns one by one.

### 8. **Manual URL Test**
Try accessing the grid data directly:
`{admin_url}/mui/index/render/namespace/artm_tradein_listing`
