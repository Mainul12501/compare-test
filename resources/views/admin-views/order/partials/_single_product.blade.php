<div class="product-card card cursor-pointer quick-view" data-product-id="{{$product->id}}">
    <div class="card-header inline_product clickable p-0">
        <img class="img--134 onerror-image" src="{{ $product['image_full_url'] ?? '' }}"
         data-onerror-image="{{asset('public/assets/admin/img/160x160/img2.jpg')}}">
    </div>

    <div class="card-body inline_product text-center p-3 clickable">
        <div class="product-title1 text-dark font-weight-bold line--limit-1">
            {{ Str::limit($product['name'], 30) }}
        </div>
        <div class="justify-content-between text-center">
            <div class="product-price text-center">
                <span class="text-accent text-dark font-weight-bold">
                    {{\App\CentralLogics\Helpers::format_currency($product['price']-\App\CentralLogics\Helpers::product_discount_calculate($product, $product['price'], $store_data)['discount_amount'])}}
                </span>
            </div>
        </div>
    </div>
</div>
<script src="{{asset('public/assets/admin')}}/js/view-pages/common.js"></script>
