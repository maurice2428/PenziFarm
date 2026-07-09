<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class LocationMapPicker extends Field
{
    protected string $view = 'filament.forms.components.location-map-picker';

    protected string $latitudeField = 'latitude';
    protected string $longitudeField = 'longitude';
    protected string $countyField = 'county';
    protected string $subCountyField = 'sub_county';
    protected string $wardField = 'ward';
    protected string $addressField = 'address';
    protected string $placeLabelField = 'place_label';

    public function latitudeField(string $field): static
    {
        $this->latitudeField = $field;

        return $this;
    }

    public function longitudeField(string $field): static
    {
        $this->longitudeField = $field;

        return $this;
    }

    public function countyField(string $field): static
    {
        $this->countyField = $field;

        return $this;
    }

    public function subCountyField(string $field): static
    {
        $this->subCountyField = $field;

        return $this;
    }

    public function wardField(string $field): static
    {
        $this->wardField = $field;

        return $this;
    }

    public function addressField(string $field): static
    {
        $this->addressField = $field;

        return $this;
    }

    public function placeLabelField(string $field): static
    {
        $this->placeLabelField = $field;

        return $this;
    }

    public function getLatitudeField(): string
    {
        return $this->latitudeField;
    }

    public function getLongitudeField(): string
    {
        return $this->longitudeField;
    }

    public function getCountyField(): string
    {
        return $this->countyField;
    }

    public function getSubCountyField(): string
    {
        return $this->subCountyField;
    }

    public function getWardField(): string
    {
        return $this->wardField;
    }

    public function getAddressField(): string
    {
        return $this->addressField;
    }

    public function getPlaceLabelField(): string
    {
        return $this->placeLabelField;
    }
}
