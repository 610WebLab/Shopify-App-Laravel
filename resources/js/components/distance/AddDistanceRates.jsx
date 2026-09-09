import React, { useState, useEffect, useCallback } from 'react';
import {
  Page,
  Layout,
  LegacyCard,
  FormLayout,
  TextField,
  Select,
  Button,
  Banner,
} from '@shopify/polaris';
import { useNavigate, useParams } from 'react-router-dom';
import { useToast } from '@shopify/app-bridge-react';

export default function AddDistanceRates() {
  const { show } = useToast();
  const navigate = useNavigate();
  const { DistanceID } = useParams();
  const [zoneId, setZoneId] = useState(0);
  const [selectedLimitOption, setSelectedLimitOption] = useState('no');
  const [selectedRates, setSelectedRates] = useState('price_based_rate');
  const [selectedWeight, setSelectedWeight] = useState('kg');
  const [formData, setFormData] = useState({
    max_delivery_rate: '',
    rate_name: '',
    description: '',
    min_order_price: '',
    max_order_price: '',
    min_order_weight: '',
    max_order_weight: '',
    min_distance: '',
    max_distance: '',
    base_delivery_price: '',
    price_per_kilometer: '',
    _method: '',
  });
  const [errors, setErrors] = useState({});

  const handleRateLimitChange = useCallback((value) => setSelectedLimitOption(value), []);
  const handleSelectedRates = useCallback((value) => setSelectedRates(value), []);
  const handleWeightChange = useCallback((value) => setSelectedWeight(value), []);

  const RateLimitOptions = [
    { label: 'No', value: 'no' },
    { label: 'Yes', value: 'yes' },
  ];
  const RatesOptions = [
    { label: 'Price Based Rate', value: 'price_based_rate' },
    { label: 'Weight Based Rate', value: 'weight_based_rate' },
  ];
  const WeightOptions = [
    { label: 'Kilograms (kg)', value: 'kg' },
    { label: 'Pounds (lb)', value: 'lb' },
    { label: 'Ounces (oz)', value: 'oz' },
  ];

  const handleInputChange = (field, value) => {
    setFormData((prev) => ({ ...prev, [field]: value }));
    setErrors((prev) => ({ ...prev, [field]: '' }));
  };

  useEffect(() => {
    if (DistanceID) {
      getDistanceRate();
    }
  }, [DistanceID]);

  async function getDistanceRate() {
    const response = await fetch('/v1/rates_by_distance/' + DistanceID);
    const result = await response.json();
    if (result.status == 1) {
      const _distance = result?.distanceRates;
      setFormData({
        max_delivery_rate: _distance?.max_delivery_rate,
        rate_name: _distance?.title,
        description: _distance?.description,
        min_order_price: _distance?.min_order_price,
        max_order_price: _distance?.max_order_price,
        min_order_weight: _distance?.min_order_weight,
        max_order_weight: _distance?.max_order_weight,
        min_distance: _distance?.min_distance,
        max_distance: _distance?.max_distance,
        base_delivery_price: _distance?.base_delivery_price,
        price_per_kilometer: _distance?.price_per_kilometer,
        _method: 'PUT',
      });
      setSelectedLimitOption(_distance?.rate_price_limit || 'no');
      setSelectedRates(_distance?.rates ? _distance?.rates : 'price_based_rate');
      setZoneId(_distance?.zone_id);
      setSelectedWeight(_distance?.weight_unit || 'kg');
    }
  }

  const validateForm = (data) => {
    const nextErrors = {};

    if (!data.rate_name || !data.rate_name.trim()) {
      nextErrors.rate_name = 'Rate name is required';
    }
    if (
      (data.max_delivery_rate == null || !data.max_delivery_rate.toString().trim())
      && selectedLimitOption === 'yes'
    ) {
      nextErrors.max_delivery_rate = 'Max delivery rate is required';
    }

    return nextErrors;
  };

  const handleSubmit = () => {
    const validationErrors = validateForm(formData);

    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      return;
    }

    const dataToSubmit = {
      ...formData,
      shop: Config.shop,
      rate_price_limit: selectedLimitOption,
      rates: selectedRates,
      selectedWeight: selectedWeight,
    };

    if (DistanceID > 0) {
      fetch(`/v1/rates_by_distance/${DistanceID}`, {
        method: 'POST',
        body: JSON.stringify(dataToSubmit),
        headers: {
          'Content-type': 'application/json',
        },
      })
        .then((res) => res.json())
        .then(
          (result) => {
            if (result.status === 1) {
              show(result.msg, { duration: 2000 });
            } else {
              show(result.msg, { duration: 2000, isError: true });
            }
          },
          (error) => {
            show(error, { duration: 2000, isError: true });
          }
        );
    } else {
      show('Distance rate is not found', { duration: 2000, isError: true });
    }
  };

  return (
    <Page
      backAction={{ content: 'Products', onAction: () => { navigate('/new/' + zoneId); } }}
      title="Distance Rate"
    >
      <Banner status="info">
        Delivery origin now comes from the store location selected on this shipping zone.
      </Banner>
      <Layout>
        <Layout.AnnotatedSection
          id="rate_name"
          title="Rate name and descriptions"
          description="Choose rate name and descriptions for each language"
        >
          <LegacyCard sectioned>
            <FormLayout>
              <TextField
                label="Rate name"
                type="text"
                name="rate_name"
                value={formData.rate_name}
                onChange={(value) => handleInputChange('rate_name', value)}
                autoComplete="off"
                error={errors.rate_name}
              />
              <TextField
                label="Rate description"
                type="text"
                name="description"
                value={formData.description}
                onChange={(value) => handleInputChange('description', value)}
                autoComplete="off"
              />
            </FormLayout>
          </LegacyCard>
        </Layout.AnnotatedSection>

        <Layout.AnnotatedSection
          id="rate_price_limit"
          title="Rate Price Limit"
          description="Limit the rate value when charging per km/m"
        >
          <LegacyCard sectioned>
            <FormLayout>
              <Select
                label="Enable maximum delivery rate"
                name="rate_price_limit"
                options={RateLimitOptions}
                onChange={handleRateLimitChange}
                value={selectedLimitOption}
              />
              {selectedLimitOption === 'yes' && (
                <TextField
                  label="Maximum delivery rate"
                  name="max_delivery_rate"
                  type="number"
                  value={formData.max_delivery_rate}
                  onChange={(value) => handleInputChange('max_delivery_rate', value)}
                  autoComplete="off"
                  error={errors.max_delivery_rate}
                />
              )}
            </FormLayout>
          </LegacyCard>
        </Layout.AnnotatedSection>

        <Layout.AnnotatedSection
          id="pricing"
          title="Pricing"
          description="Set a fixed base price and/or a per-kilometer charge. If both are set, only the per-kilometer price is used."
        >
          <LegacyCard sectioned>
            <FormLayout>
              <TextField
                type="number"
                label="Base delivery price"
                name="base_delivery_price"
                value={formData.base_delivery_price}
                onChange={(value) => handleInputChange('base_delivery_price', value)}
                autoComplete="off"
                helpText="Fixed shipping amount charged for this rate, regardless of distance. Used only when Delivery price per kilometer is empty or 0."
              />
              <TextField
                type="number"
                label="Delivery price per kilometer"
                name="price_per_kilometer"
                value={formData.price_per_kilometer}
                onChange={(value) => handleInputChange('price_per_kilometer', value)}
                autoComplete="off"
                helpText="Charged as: price × distance in km (store location → customer address). Example: 1 × 10 km = 10.00. When this is set, Base delivery price is ignored."
              />
            </FormLayout>
          </LegacyCard>
        </Layout.AnnotatedSection>

        <Layout.AnnotatedSection
          id="rates"
          title="Rates"
          description="Choose between price and weight-based rates"
        >
          <LegacyCard sectioned>
            <FormLayout>
              <Select
                label="Rates"
                options={RatesOptions}
                onChange={handleSelectedRates}
                value={selectedRates}
              />
              {selectedRates === 'weight_based_rate' && (
                <Select
                  label="Weight Unit"
                  options={WeightOptions}
                  onChange={handleWeightChange}
                  value={selectedWeight}
                />
              )}
            </FormLayout>
          </LegacyCard>
        </Layout.AnnotatedSection>

        <Layout.AnnotatedSection
          id="rate_limits"
          title="Rate Limits"
          description="Only show this rate when the order is within limits. Leave blank if not applicable."
        >
          <LegacyCard sectioned>
            <FormLayout>
              {selectedRates === 'price_based_rate' && (
                <FormLayout.Group>
                  <TextField
                    type="number"
                    label="Minimum order price"
                    name="min_order_price"
                    value={formData.min_order_price}
                    onChange={(value) => handleInputChange('min_order_price', value)}
                    autoComplete="off"
                  />
                  <TextField
                    type="number"
                    label="Maximum order price"
                    name="max_order_price"
                    value={formData.max_order_price}
                    onChange={(value) => handleInputChange('max_order_price', value)}
                    autoComplete="off"
                  />
                </FormLayout.Group>
              )}
              {selectedRates === 'weight_based_rate' && (
                <FormLayout.Group>
                  <TextField
                    type="number"
                    label="Minimum order weight"
                    name="min_order_weight"
                    value={formData.min_order_weight}
                    onChange={(value) => handleInputChange('min_order_weight', value)}
                    autoComplete="off"
                  />
                  <TextField
                    type="number"
                    label="Maximum order weight"
                    name="max_order_weight"
                    value={formData.max_order_weight}
                    onChange={(value) => handleInputChange('max_order_weight', value)}
                    autoComplete="off"
                  />
                </FormLayout.Group>
              )}
              <FormLayout.Group>
                <TextField
                  type="number"
                  label="Minimum distance"
                  name="min_distance"
                  value={formData.min_distance}
                  onChange={(value) => handleInputChange('min_distance', value)}
                  autoComplete="off"
                />
                <TextField
                  type="number"
                  label="Maximum distance"
                  name="max_distance"
                  value={formData.max_distance}
                  onChange={(value) => handleInputChange('max_distance', value)}
                  autoComplete="off"
                />
              </FormLayout.Group>
            </FormLayout>
          </LegacyCard>
        </Layout.AnnotatedSection>

        <Layout.AnnotatedSection>
          <LegacyCard sectioned>
            <Button primary onClick={handleSubmit}>
              Save
            </Button>
          </LegacyCard>
        </Layout.AnnotatedSection>
      </Layout>
    </Page>
  );
}
