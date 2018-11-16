<?php

namespace Vyuldashev\NovaMoneyField;

use Money\Currencies\AggregateCurrencies;
use Money\Currencies\BitcoinCurrencies;
use Money\Currency;
use Laravel\Nova\Fields\Number;
use Money\Currencies\ISOCurrencies;
use Laravel\Nova\Http\Requests\NovaRequest;

class Money extends Number
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'nova-money-field';

    public $inMinorUnits;

    public function __construct($name, $currency = 'USD', $attribute = null, $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        $this->withMeta([
            'currency' => $currency,
            'subUnits' => $this->subunits($currency),
        ]);

        $this
            ->resolveUsing(function ($value) use ($currency) {
                return $this->inMinorUnits ? $value / $this->minorUnit($currency) : $value;
            })
            ->fillUsing(function (NovaRequest $request, $model, $attribute, $requestAttribute) use ($currency) {
                $value = $request[$requestAttribute];

                if ($this->inMinorUnits) {
                    $value *= $this->minorUnit($currency);
                }

                $model->{$attribute} = $value;
            });
    }

    /**
     * The value in database is store in minor units (cents for dollars).
     */
    public function storedInMinorUnits()
    {
        $this->inMinorUnits = true;

        return $this;
    }

    public function locale($locale)
    {
        return $this->withMeta(['locale' => $locale]);
    }

    public function subUnits(string $currency)
    {
        return (new AggregateCurrencies([
            new ISOCurrencies(),
            new BitcoinCurrencies(),
        ]))->subunitFor(new Currency($currency));
    }

    public function minorUnit($currency)
    {
        return 10 ** $this->subUnits($currency);
    }
}
