<?php


class InvoiceImage {
    
    public $image = array();
    public $invoiceId = "";
    public $print = false;
    public $deliveryDate = '';

    public function generate($invoiceId, $print_ver = false)
    {
        $this->invoiceId = $invoiceId;
        
        $this->font_file = $font_file = dirname(__FILE__). "/../../storage/invoice_template/NotoSansHant-Medium.otf";
        
        $image_template = dirname(__FILE__). "/../../storage/invoice_template/PKH_Invoice_SAMPLE_2014_DEC_22.jpg";

        
        # Retrieve the invoice data
    //  $base = Invoice::where('invoiceId', $invoiceId);
    //  $invoice = Invoice::categorizePendingInvoice(Invoice::getFullInvoice($base));

        $itemIds = array('桶', '排', '扎', '箱');

        $ids = "'" . implode("','", $itemIds) . "'";
        $invoices = Invoice::where('invoiceId', $invoiceId)
                ->with(['invoiceItem' => function ($query) use ($ids) {
                    $query->orderBy('productLocation','asc')->orderBy('productQtyUnit','asc')->orderByRaw(DB::raw("FIELD(productUnitName, $ids) DESC"))->orderBy('productId','asc');
                }])->with('client', 'staff')
                ->first();

      //  pd($invoices);

        $total = $invoices->count();

        // get product information
        $productId = [];
        if(count($invoices) > 0)
        {

                $invoiceTotal = 0;
                foreach($invoices->invoiceItem as $item)
                {
                    $productId[] = $item->productId;
                    $invoiceTotal += $item->productQty * $item->productPrice * (100-$item->productDiscount)/100;
                }
            $invoices->totalAmount = $invoiceTotal;
            $this->deliveryDate = $invoices->deliveryDate;

            $products = Product::wherein('productId', $productId)->get();
            foreach($products as $product)
            {//dd($product->toArray());
                $newProductSet[$product->productId] = $product->toArray();
            }


                foreach($invoices->invoiceItem as $item)
                {
                    $item->productInfo = $newProductSet[$item->productId];
                }

        }

        $invoice = [
            'count' => $total,
            'invoices' => $invoices->toArray(),
        ];

//pd($invoice);

       $i = $invoice['invoices'];

   //  pd($i);
        $adv = InvoicePrintFormat::select('advertisement')->where('from', '<=', $i['deliveryDate'])->where('to', '>=', $i['deliveryDate'])->orderby('ipfId', 'desc')->first();
                
        
        
        # Setting about the invoice template
        $max_item_per_section = ($i['invoiceRemark'] ? 6 : 8);
        $number_of_item = count($i['invoice_item']);
        $section_required = ceil($number_of_item / $max_item_per_section);
        
        $item_counter = 1;
        $items_chunk = array_chunk($i['invoice_item'], $max_item_per_section, false);

        //pd($items_chunk);

        # Now Process with each section
        foreach($items_chunk as $p => $sections_items)
        {
            /*
             * Retrieve image template from source file
             * Last receive date: 2014 DEC 22
             * Format JPG
             */

$debug = 0;

            if($debug){
                $this->print = true;
                $this->image[$p] = Image::make($image_template);
                $this->image[$p]->resize(1654, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }
             else if($print_ver)
            {
                $this->print = true;
                $this->image[$p] = Image::canvas(1654, 1200);
            }
            else
            {
                $this->print = false;
                $this->image[$p] = Image::make($image_template);
                $this->image[$p]->resize(1654, null, function ($constraint) {
                    $constraint->aspectRatio();
                });
                
            }
            
            
            
        
            /*
             * ===========================================================================================
             *                                      Header Information
             * ===========================================================================================
            */
    
            /*
             * Add Direct Line information to customer
             * Position 155W 230H
             * Font Size: 37
            */
    
            $this->image[$p]->text("2455 2266", 155, 230, function($font) use($font_file) {
                $font->file($font_file);
                $font->size(37);
                $font->color('#000000');
            });
    
            /*
             * Add client name to the invoice image
             * Position: 155W 280H
             * Font Size: 35
            */

            $this->image[$p]->text($i['client']['customerName_chi'] . '('.$i['client']['customerId'].')', 155, 280, function($font) use($font_file) {
                $font->file($font_file);
                $font->size(35);
                $font->color('#000000');
            });
            
            $max_length = 22;
            $address_splits = str_split_unicode($i['client']['address_chi'], $max_length);
            $address = implode("\n", $address_splits);  
            
            $this->image[$p]->text($address, 155, 320, function($font) use($font_file) {
                $font->file($font_file);
                $font->size(30);
                $font->color('#000000');
            });
    
            $this->image[$p]->text("車線 " .str_pad($i['zoneId'], 2, '0', STR_PAD_LEFT) ."/".str_pad($i['routePlanningPriority'], 2, '0', STR_PAD_LEFT), 1002, 350, function($font) use($font_file) {
                $font->file($font_file);
                $font->size(35);
                $font->color('#000000');
            });
            
            $this->image[$p]->text($i['staff']['name'], 1000, 310, function($font) use($font_file) {
                $font->file($font_file);
                $font->size(30);
                $font->color('#000000');
            });
    
    
            /*
             * Add Invoice Number to the invoice image
             * Position: 1370W 205H
             * Font Size: 30
            */
            $this->image[$p]->text($i['invoiceId'], 1370, 205, function($font) use($font_file) {
                $font->file($font_file);
                $font->size(30);
                $font->color('#000000');
            });
    
            /*
             * Add Invoice Date to the invoice image
             * Position:
             * Font Size:
            */
            $this->image[$p]->text(date("Y-m-d", $i['invoiceDate']), 1370, 253, function($font) use($font_file) {
                $font->file($font_file);
                $font->size(30);
                $font->color('#000000');
            });
    
            /*
             * Add Customer Reference to the invoice image
             * Position:
             * Font Size:
            */
            $reference = ($i['customerRef'] == "" ? "-----------------" : $i['customerRef']);
            $this->image[$p]->text($reference, 1370, 298, function($font) use($font_file) {
                $font->file($font_file);
                $font->size(30);
                $font->color('#000000');
            });
    
            /*
             * Add Payment Method to the invoice image
             * Position:
             * Font Size:
            */
            $paymentterms = $i['paymentTerms'] == "1" ? "C.O.D." : "Credit";
            $this->image[$p]->text($paymentterms, 1370, 343, function($font) use($font_file) {
                $font->file($font_file);
                $font->size(30);
                $font->color('#000000');
            });

        /*    $this->image[$p]->text('(折扣)', 1480, 418, function($font) use($font_file) {
                $font->file($font_file);
                $font->size(25);
                $font->color('#FFFFFF');
            }); */

            /*
             * Add Page Information to the invoice image
            */
            
            $current_page = $p + 1;
            $page_text = "P. $current_page / $section_required";
            $this->image[$p]->text($page_text, 1540, 1185, function($font) use($font_file) {
                $font->file($font_file);
                $font->size(30);
                $font->color('#000000');
            });
            
           if($adv != null)
            {
                $max_length = 32;
              //  $adv_splits = str_split_unicode($adv->advertisement, $max_length);
            //   $adv1 = implode("\n", $adv_splits);


                $this->image[$p]->text($adv->advertisement, 400, 930, function($font) use($font_file) {
                    $font->file($font_file);
                    $font->size(35);
                    $font->color('#000000');
                });
            }
            
            /*
             * ===========================================================================================
             *                                      Invoice Information
             * ===========================================================================================
            */
            $position = [
                'y' => 480,
                'row_interval' => 50, //(400 / $max_item_per_section),
            ];

            $line_total = 1520;
            foreach($sections_items as $check){
               if($check['productDiscount'] > 0)
                   $line_total = 1520;
            }

            foreach($sections_items as $item)
            {
                /*
                 * Add Item Counter
                 */
                $this->image[$p]->text($item_counter, 60, $position['y'], function($font) use($font_file) {
                    $font->file($font_file);
                    $font->size(30);
                    $font->color('#000000');
                });
                /*
                 * Add Product ID
                */
                $this->image[$p]->text($item['productId'], 120, $position['y'], function($font) use($font_file) {
                    $font->file($font_file);
                    $font->size(30);
                    $font->color('#000000');
                });

                /*
                 * Add Product Name and Specification
                */
                $limited_remark = str_limit($item['productRemark'], 10, "");
                $productName = $item['productInfo']['productName_chi'] . ($item['productRemark'] ? '***' : '') . $limited_remark;
                //var_dump($limited_remark, 35-strlen($item['productInfo']['productName_chi']));
                $this->image[$p]->text($productName, 300, $position['y'], function($font) use($font_file) {
                    $font->file($font_file);
                    $font->size(30);
                    $font->color('#000000');
                });

                
                /*
                 * Add Qty
                */
                if($i['invoiceStatus']==98){
                    $i['invoiceTotalAmount'] *= -1;
                    $i['amount'] *= -1;
                    $item['productQty'] *= -1;
            }
                $qty_text = number_format($item['productQty'],1,'.',',') . ' ' .str_replace(' ', '', $item['productInfo']['productPackingName_' . $item['productQtyUnit']]);
                $this->image[$p]->text($qty_text, 1180, $position['y'], function($font) use($font_file) {
                    $font->file($font_file);
                    $font->size(30);
                    $font->color('#000000');
                    $font->align('right');
                });

                /*
                 * Add Product Price
                */
                $price = round($item['productPrice'],1);
                $this->image[$p]->text('$'.number_format($price,1,'.',','), 1310, $position['y'], function($font) use($font_file) {
                    $font->file($font_file);
                    $font->size(30);
                    $font->color('#000000');
                    $font->align('right');
                });

                /*
                 * Add Item Price
                */
                $item_price = round($item['productPrice'] * $item['productQty'] * (100-$item['productDiscount'])/100,1);
                $this->image[$p]->text('$'.number_format($item_price,2,'.',','), $line_total, $position['y'], function($font) use($font_file) {
                    $font->file($font_file);
                    $font->size(30);
                    $font->color('#000000');
                    $font->align('right');
                });

                /*
                 * Add % off
                */
                if($item['productDiscount'] > 0){
                    $item_price = '('.$item['productDiscount'].'%)';
                    $this->image[$p]->text($item_price, 1030, $position['y'], function($font) use($font_file) {
                        $font->file($font_file);
                        $font->size(25);
                        $font->color('#000000');
                        $font->align('right');
                    });
                }

                $position['y'] += $position['row_interval'];
                $item_counter++;

            }
            
            $this->image[$p]->text($i['invoiceRemark'], 300, 800, function($font) use($font_file) {
                $font->file($font_file);
                $font->size(30);
                $font->color('#000000');
            });
        }
        
  //      $total_amount = "合計  HKD " . $english_format_number = number_format(round($i['totalAmount']*$i['invoiceDiscount'],1), 2, '.', ',');;
       // $total_amount = "合計  HKD " . $i['invoiceTotalAmount'];

        $this->image[$p]->text('HKD ' . number_format(round($i['invoiceTotalAmount']/((100-$i['invoiceDiscount'])/100),1  ),2,'.',','), 1550, 900, function($font) use($font_file) {
            $font->file($font_file);
            $font->size(40);
            $font->color('#000000');
            $font->align('right');
        });
if($i['invoiceDiscount'] > 0){
        $this->image[$p]->text('- ('. $i['invoiceDiscount'].'%)', 1550, 950, function($font) use($font_file) {
            $font->file($font_file);
            $font->size(40);
            $font->color('#000000');
            $font->align('right');
        });

        $this->image[$p]->text('HKD '. number_format($i['amount'],2,'.',','), 1550, 1000, function($font) use($font_file) {
            $font->file($font_file);
            $font->size(40);
            $font->color('#000000');
            $font->align('right');
        });
}
        return $this;
    }
    
    public function show($page)
    {
        /*
         * return image directly.
         */
        header('Content-Type: image/png');
        echo $this->image[$page]->encode('png');
        
    }

    public function saveAll()
    {
        foreach($this->image as $page=>$i)
        {
            // Path: gs://lpk-general-bulk/invoices_image/{YYMM}/{invoiceId}-{pageNumber}.png
            // Todo: change to independent method
            
            $numericpagenumber = (string) $page + 1;
            $filename = ($this->print ? 'print_' : 'preview/preview_') . $this->invoiceId . '-' . $numericpagenumber . ".png";
           // $fullpath = storage_path().'/invoices_images/'. str_replace('I', '', $k[0]) .'/'.$filename;



            $fullpath = public_path($filename);

            $filenames[$page]['filename'] = $filename;
            $filenames[$page]['deliveryDate'] =  $this->deliveryDate;
            $filenames[$page]['fullpath'] = $fullpath;


            if (!file_exists(public_path() . '/'.date('Y-m', $this->deliveryDate)))
                mkdir(public_path() . '/'.date('Y-m', $this->deliveryDate), 0777, true);
            if (!file_exists(public_path() . '/'.date('Y-m', $this->deliveryDate).'/'.date('d', $this->deliveryDate)))
                mkdir(public_path() . '/'.date('Y-m', $this->deliveryDate).'/'.date('d', $this->deliveryDate), 0777, true);
            $i->save(public_path() . '/'.date('Y-m', $this->deliveryDate).'/'.date('d', $this->deliveryDate).'/'.$filename);

            $i->save($fullpath);
            $i->destroy();


           /*     $im = imagecreatefrompng($fullpath);

                list($dst_width, $dst_height) = getimagesize($fullpath);

                $newImg = imagecreatetruecolor($dst_width, $dst_height);

                imagealphablending($newImg, false);
                imagesavealpha($newImg, true);
                $transparent = imagecolorallocatealpha($newImg, 255, 255, 255, 127);
                imagefilledrectangle($newImg, 0, 0, $dst_width, $dst_height, $transparent);
                imagecopyresampled($newImg, $im, 0, 0, 0, 0, $dst_width, $dst_height, $dst_width, $dst_height);
                imagepng($newImg, $filename,9);*/

                //  return $newImg;


        }





        return $filenames;
    }
	
    public function preview($page)
    {
        $this->image[$page]->text("PREVIEW", 60, 80, function($font)  {
            $font->file($this->font_file);
            $font->size(30);
            $font->color('#000000');
            //$font->align('right');
        });
            header('Content-Type: image/jpg');
        echo $this->image[$page]->encode('jpg');
    }
}