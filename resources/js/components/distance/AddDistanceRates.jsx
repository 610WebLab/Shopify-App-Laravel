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
  const [isLoaded, setIsLoaded] = useState(false);
  // States for select fields
  const [zoneId, setZoneId] = useState(0);
  const [selectedLimitOption, setSelectedLimitOption] = useState('no');
  const [selectedLatitudeLongitude, setSelectedLatitudeLongitude] = useState('no');
  const [selectedRates, setSelectedRates] = useState('price_based_rate');
  const [selectedWeight, setSelectedWeight] = useState('kg');

  // State for form fields
  const [formData, setFormData] = useState({
    location_name: '',
    country_region: '',
    city: '',
    street: '',
    postal_code: '',
    max_delivery_rate: '',
    latitude: '',
    longitude: '',
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
    _method: ''
  });
  const [shopLocationOption, setShopLocationOption] = useState([]);
  const [shopLocation, setShopLocation] = useState([]);
  // State for validation errors
  const [errors, setErrors] = useState({});
  // Handlers for select fields
  const handleRateLimitChange = useCallback((value) => setSelectedLimitOption(value), []);
  const handleLatitudeLongitudeChange = useCallback((value) => setSelectedLatitudeLongitude(value), []);
  const handleSelectedRates = useCallback((value) => setSelectedRates(value), []);

  // Options for select fields
  const RateLimitOptions = [
    { label: 'No', value: 'no' },
    { label: 'Yes', value: 'yes' },
  ];
  const LatitudeLongitudeOptions = [
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

  const handleWeightChange = useCallback((value) => setSelectedWeight(value), []);
  const handleInputChange = (field, value) => {
    if (field === "location_name") {
      const matchedLocation = shopLocation.find(location => {
        const locationId = location.id.replace('gid://shopify/Location/', ''); // Extract the numeric part
        return locationId === value; // Compare with the target ID
      });
      setFormData((prev) => ({
        ...prev,
        location_name: value,
        country_region: matchedLocation ? matchedLocation.address.country : "",
        city: matchedLocation ? matchedLocation.address.city : "",
        street: matchedLocation ? matchedLocation.address.address1 : "",
        postal_code: matchedLocation ? matchedLocation.address.zip : "",
        latitude: matchedLocation ? matchedLocation.address.latitude : "",
        longitude: matchedLocation ? matchedLocation.address.longitude : "",
      }))
    } else {
      setFormData((prev) => ({ ...prev, [field]: value }));
      setErrors((prev) => ({ ...prev, [field]: '' }));
    }
  };
  useEffect(() => {
    getShopLocations();
  }, []);
  async function getShopLocations() {
    setIsLoaded(false);
    const response = await fetch(`/v1/locations?shop=${Config.shop}`);
    const result = await response.json();
    if (result.status) {
      setShopLocation(result.data);
      const formattedLocations = result.data.map((location) => ({
        label: location.name || '',
        value: location.id.replace('gid://shopify/Location/', '') || ''
      }));
      let defaultopt = { label: 'Custom', value: '' };
      setShopLocationOption([defaultopt, ...formattedLocations]);
    } else {
      console.error("Error fetching locations: ", result.message || "Unknown error");
    }
  }
  useEffect(() => {
    if (DistanceID) {
      getDistanceRate();
    }
  }, [DistanceID])

  async function getDistanceRate() {
    setIsLoaded(false);
    const response = await fetch('/v1/rates_by_distance/' + DistanceID);
    const result = await response.json();
    if (result.status == 1) {
      const _distance = result?.distanceRates;
      setFormData({
        location_name: _distance?.location_name,
        country_region: _distance?.country_region,
        city: _distance?.city,
        street: _distance?.street,
        postal_code: _distance?.postal_code,
        max_delivery_rate: _distance?.max_delivery_rate,
        latitude: _distance?.latitude,
        longitude: _distance?.longitude,
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
        _method: 'PUT'
      });
      setSelectedLimitOption(_distance?.rate_price_limit);
      setSelectedLatitudeLongitude(_distance?.set_latitude_longitude);
      setSelectedRates(_distance?.rates ? _distance?.rates : 'price_based_rate');
      setZoneId(_distance?.zone_id);
      setSelectedWeight(_distance?.weight_unit)
    }
  }
  // New validation function
  const validateForm = (data) => {
    const errors = {};

    if (!data.country_region || !data.country_region.trim()) {
      errors.country_region = 'Country/region is required';
    }
    if (!data.city || !data.city.trim()) {
      errors.city = 'City is required';
    }
    if (!data.street || !data.street.trim()) {
      errors.street = 'Street is required';
    }
    if (!data.postal_code || !data.postal_code.trim()) {
      errors.postal_code = 'Postal code is required';
    }
    if (!data.rate_name || !data.rate_name.trim()) {
      errors.rate_name = 'Rate name is required';
    }
    if ((data.max_delivery_rate == null || !data.max_delivery_rate.toString().trim()) && selectedLimitOption === "yes") {
      errors.max_delivery_rate = 'Max delivery rate is required';
    }
    if ((data.latitude == null || !data.latitude.toString().trim())) {
      errors.latitude = 'Latitude is required';
    }
    if ((data.longitude == null || !data.longitude.toString().trim())) {
      errors.longitude = 'Longitude is required';
    }

    return errors;
  };


  const handleSubmit = () => {
    // Call the new validation function
    const validationErrors = validateForm(formData);

    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      return;
    }

    // Combine select values with text fields
    const dataToSubmit = {
      ...formData,
      shop: Config.shop,
      rate_price_limit: selectedLimitOption,
      set_latitude_longitude: selectedLatitudeLongitude,
      rates: selectedRates,
      selectedWeight: selectedWeight
    };
    if (DistanceID > 0) {
      fetch(`/v1/rates_by_distance/${DistanceID}`, {
        method: "POST",
        body: JSON.stringify(dataToSubmit),
        headers: {
          "Content-type": "application/json"
        }

      }).then(res => res.json()).then((result) => {
        if (result.status === 1) {
          show(result.msg, { duration: 2000 })
        } else {
          show(result.msg, { duration: 2000, isError: true });
        }
      }, (error) => {
        show(error, { duration: 2000, isError: true });
      });
    } else {
      show("Distance rate is not found", { duration: 2000, isError: true });
    }
    // Proceed to submit dataToSubmit as needed
  };

  // Create an array of error messages from the errors object
  const errorMessages = Object.values(errors);
  return (
    <Page backAction={{ content: 'Products', onAction: (() => { navigate('/new/' + zoneId); }) }} title="Distance Rate">
      {/* Display errors in a Banner if there are any */}
      {/* {errorMessages.length > 0 && (
        <Banner title="Please fix the following errors:" status="critical" onDismiss={() => setErrors({})}>
          <ul>
            {errorMessages.map((error, index) => (
              <li key={index}>{error}</li>
            ))}
          </ul>
        </Banner>
      )} */}
      <Layout>
        {/* Rate Name and Descriptions Section */}
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

        {/* Address Section */}
        <Layout.AnnotatedSection
          id="address"
          title="Store location"
          description="We’ll use this store location as a starting point when calculating your delivery rates"
        >
          <LegacyCard sectioned>
            <FormLayout>
              <Select
                label="Location name"
                name="location_name"
                options={shopLocationOption}
                onChange={(value) => handleInputChange('location_name', value)}
                value={formData.location_name}
                error={errors.location_name}
              />
              {/* <TextField
                label="Location name"
                type="text"
                name="location_name"
                value={formData.location_name}
                onChange={(value) => handleInputChange('location_name', value)}
                autoComplete="off"
                error={errors.location_name}
              /> */}
              <TextField
                label="Country/region"
                type="text"
                name="country_region"
                value={formData.country_region}
                onChange={(value) => handleInputChange('country_region', value)}
                autoComplete="off"
                error={errors.country_region}
              />
              <TextField
                label="City"
                type="text"
                name="city"
                value={formData.city}
                onChange={(value) => handleInputChange('city', value)}
                autoComplete="off"
                error={errors.city}
              />
              <TextField
                label="Street and house number"
                type="text"
                name="street"
                value={formData.street}
                onChange={(value) => handleInputChange('street', value)}
                autoComplete="off"
                error={errors.street}
              />
              <TextField
                label="Postal code"
                type="text"
                name="postal_code"
                value={formData.postal_code}
                onChange={(value) => handleInputChange('postal_code', value)}
                autoComplete="off"
                error={errors.postal_code}
              />
              <TextField
                label="Latitude"
                type="number"
                name="latitude"
                value={formData.latitude}
                onChange={(value) => handleInputChange('latitude', value)}
                autoComplete="off"
                error={errors.latitude}
              />
              <TextField
                label="Longitude"
                type="number"
                name="longitude"
                value={formData.longitude}
                onChange={(value) => handleInputChange('longitude', value)}
                autoComplete="off"
                error={errors.longitude}
              />
            </FormLayout>
          </LegacyCard>
        </Layout.AnnotatedSection>



        {/* Rate Price Limit Section */}
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

        {/* Advanced Section */}
        {/* <Layout.AnnotatedSection
          id="advanced"
          title="Advanced"
          description="Manually set your location's latitude and longitude if necessary"
        >
          <LegacyCard sectioned>
            <FormLayout>
              <Select
                label="Manually set latitude and longitude"
                name="set_latitude_longitude"
                options={LatitudeLongitudeOptions}
                onChange={handleLatitudeLongitudeChange}
                value={selectedLatitudeLongitude}
              />
              {selectedLatitudeLongitude === 'yes' && (
                <>

                </>
              )}
            </FormLayout>
          </LegacyCard>
        </Layout.AnnotatedSection> */}

        {/* Pricing Section */}
        <Layout.AnnotatedSection
          id="pricing"
          title="Pricing"
          description="Edit the base delivery price and delivery price per kilometer"
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
              />
              <TextField
                type="number"
                label="Delivery price per kilometer"
                name="price_per_kilometer"
                value={formData.price_per_kilometer}
                onChange={(value) => handleInputChange('price_per_kilometer', value)}
                autoComplete="off"
              />
            </FormLayout>
          </LegacyCard>
        </Layout.AnnotatedSection>

        {/* Rates Section */}
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

        {/* Rate Limits Section */}
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

        {/* Submit Button */}
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
