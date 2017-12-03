# Laravel DHL API

I needed a laravel-esque wrapper to make calls to the DHL XML service, but couldn't find any. So I created one. It was specifically designed for my own personal use, but you are welcome to submit issues, and I'll look into refactoring it so that it can be used in a more general purpose fashion.

## Getting Started

### Prerequisites

This was built and tested ONLY on Laravel 5.5, although I'm sure it'll work on previous versions as well.

### Installing

```
composer require misterbrownrsa/laravel-dhl-tas
```

Since Laravel 5.5 automatically includes the service provider, it won't be necessary to register it. However, if you really want to, run the following command

```

```

##Usage Examples

Trade Automation Services provides duties and tax details when trying to calculate total landed cost for a shipment

```
$products = [];
foreach ($cart->items as $cartItem) {
    $products[] = [
        'name'     => $cartItem->warehouse->product->name,
        'price'    => $cartItem->price,
        'quantity' => $cartItem->quantity,
        'weight'   => $cartItem->warehouse->product->weight,
        'hscode'   => "6404.1900", //retrieved from their HSCode systems
    ];
}

$TAS = new \MisterBrownRSA\DHL\TAS\DHLTAS();
$TAS->addProduct($products);
$TAS->total($cart->subtotal);
$TAS->reference('A1AQV');
$TAS->toCountry('ZW');
$results = $TAS->doCurlPost();
```

Dump the request
```
dump($TAS->toXMML());
```

Dump the response
```
dump($TAS->doCurlPost());
```

## Authors

* **Duwayne Brown** - *Initial work* - [MisterBrownRSA](https://github.com/MisterBrownRSA)

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* Thanks David for your help during the implementation process