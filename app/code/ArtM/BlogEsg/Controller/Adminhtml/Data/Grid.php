<?php

namespace ArtM\BlogEsg\Controller\Adminhtml\Data;

use ArtM\BlogEsg\Controller\Adminhtml\Data;

class Grid extends Data
{
   /**
     * @return void
     */
   public function execute()
   {
      return $this->_resultPageFactory->create();
   }
}