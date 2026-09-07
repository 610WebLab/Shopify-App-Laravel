import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
  Page,
  Layout,
  LegacyCard,
  FormLayout,
  TextField,
  Select,
  Checkbox,
  Button,
  Banner,
  Text,
  Spinner,
  DropZone,
  Thumbnail,
  Box,
} from '@shopify/polaris';
import { useToast } from '@shopify/app-bridge-react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

const emptyForm = {
  company_name: '',
  phone: '',
  email: '',
  website: '',
  address_source: 'store',
  location_id: '',
  location_name: '',
  address1: '',
  address2: '',
  city: '',
  state: '',
  zip: '',
  country: '',
  default_template_id: '',
  include_barcode: true,
  logo_url: '',
};

const LabelSettingsPage = () => {
  const { show } = useToast();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState(emptyForm);
  const [addresses, setAddresses] = useState([]);
  const [templates, setTemplates] = useState([]);
  const [logoFile, setLogoFile] = useState(null);
  const [logoPreview, setLogoPreview] = useState('');
  const [removeLogo, setRemoveLogo] = useState(false);
  const [error, setError] = useState('');

  const updateField = useCallback((key, value) => {
    setForm((prev) => ({
      ...prev,
      [key]: value,
    }));
  }, []);

  const applyAddressOption = useCallback((option) => {
    if (!option) {
      return;
    }

    setForm((prev) => ({
      ...prev,
      address_source: option.source,
      location_id: option.source === 'location' ? option.id : '',
      location_name: option.name || '',
      company_name: prev.company_name || option.company_name || '',
      phone: prev.phone || option.phone || '',
      email: prev.email || option.email || '',
      address1: option.address1 || '',
      address2: option.address2 || '',
      city: option.city || '',
      state: option.state || '',
      zip: option.zip || '',
      country: option.country || '',
    }));
  }, []);

  const loadData = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const [settingsRes, addressesRes, templatesRes] = await Promise.all([
        axios.get(`/label-settings?shop=${Config.shop}`),
        axios.get(`/label-settings/addresses?shop=${Config.shop}`),
        axios.get(`/get-templates?shop=${Config.shop}`),
      ]);

      const addressOptions = addressesRes.data?.status ? (addressesRes.data.data || []) : [];
      setAddresses(addressOptions);

      const templateOptions = templatesRes.data?.status
        ? (templatesRes.data.templates || []).map((template) => ({
          label: template.name,
          value: String(template.id),
        }))
        : [];
      setTemplates([
        { label: 'No default template', value: '' },
        ...templateOptions,
      ]);

      if (settingsRes.data?.status && settingsRes.data.data) {
        const data = settingsRes.data.data;
        setForm({
          company_name: data.company_name || '',
          phone: data.phone || '',
          email: data.email || '',
          website: data.website || '',
          address_source: data.address_source || 'store',
          location_id: data.location_id || '',
          location_name: data.location_name || '',
          address1: data.address1 || '',
          address2: data.address2 || '',
          city: data.city || '',
          state: data.state || '',
          zip: data.zip || '',
          country: data.country || '',
          default_template_id: data.default_template_id ? String(data.default_template_id) : '',
          include_barcode: data.include_barcode !== false,
          logo_url: data.logo_url || '',
        });
        setLogoPreview(data.logo_url || '');
      } else if (addressOptions.length > 0) {
        applyAddressOption(addressOptions[0]);
      }
    } catch (err) {
      const message = err?.response?.data?.message || 'Failed to load label settings';
      setError(message);
      show(message, { duration: 2500, isError: true });
    } finally {
      setLoading(false);
    }
  }, [applyAddressOption, show]);

  useEffect(() => {
    loadData();
  }, [loadData]);

  const addressSelectOptions = useMemo(() => (
    addresses.map((address) => ({
      label: address.label,
      value: address.id,
    }))
  ), [addresses]);

  const selectedAddressValue = form.address_source === 'location'
    ? (form.location_id || '')
    : 'store';

  const handleAddressSelect = useCallback((value) => {
    const option = addresses.find((item) => item.id === value);
    applyAddressOption(option);
  }, [addresses, applyAddressOption]);

  const handleLogoDrop = useCallback((_dropFiles, acceptedFiles) => {
    const file = acceptedFiles[0];
    if (!file) {
      return;
    }

    setLogoFile(file);
    setRemoveLogo(false);
    setLogoPreview(URL.createObjectURL(file));
  }, []);

  const handleRemoveLogo = useCallback(() => {
    setLogoFile(null);
    setLogoPreview('');
    setRemoveLogo(true);
  }, []);

  const handleSave = useCallback(async () => {
    setSaving(true);
    setError('');

    try {
      const payload = new FormData();
      payload.append('shop', Config.shop);
      payload.append('_token', Config.csrf_token);
      payload.append('company_name', form.company_name || '');
      payload.append('phone', form.phone || '');
      payload.append('email', form.email || '');
      payload.append('website', form.website || '');
      payload.append('address_source', form.address_source || 'store');
      payload.append('location_id', form.location_id || '');
      payload.append('location_name', form.location_name || '');
      payload.append('address1', form.address1 || '');
      payload.append('address2', form.address2 || '');
      payload.append('city', form.city || '');
      payload.append('state', form.state || '');
      payload.append('zip', form.zip || '');
      payload.append('country', form.country || '');
      payload.append('default_template_id', form.default_template_id || '');
      payload.append('include_barcode', form.include_barcode ? '1' : '0');
      payload.append('remove_logo', removeLogo ? '1' : '0');

      if (logoFile) {
        payload.append('logo', logoFile);
      }

      const response = await axios.post('/label-settings', payload, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      if (!response.data?.status) {
        throw new Error(response.data?.message || 'Failed to save label settings');
      }

      const saved = response.data.data;
      setForm((prev) => ({
        ...prev,
        logo_url: saved.logo_url || '',
      }));
      setLogoPreview(saved.logo_url || '');
      setLogoFile(null);
      setRemoveLogo(false);
      show(response.data.message || 'Label settings saved successfully', { duration: 2000 });
    } catch (err) {
      const message = err?.response?.data?.message || err.message || 'Failed to save label settings';
      setError(message);
      show(message, { duration: 2500, isError: true });
    } finally {
      setSaving(false);
    }
  }, [form, logoFile, removeLogo, show]);

  if (loading) {
    return (
      <Page
        title="Label Settings"
        backAction={{
          content: 'Templates',
          onAction: () => navigate('/pages/templates'),
        }}
      >
        <Box padding="500">
          <div style={{ display: 'flex', justifyContent: 'center' }}>
            <Spinner accessibilityLabel="Loading label settings" size="large" />
          </div>
        </Box>
      </Page>
    );
  }

  return (
    <Page
      title="Label Settings"
      backAction={{
        content: 'Templates',
        onAction: () => navigate('/pages/templates'),
      }}
      primaryAction={{
        content: 'Save',
        onAction: handleSave,
        loading: saving,
      }}
    >
      <Layout>
        {error ? (
          <Layout.Section>
            <Banner status="critical" onDismiss={() => setError('')}>
              {error}
            </Banner>
          </Layout.Section>
        ) : null}

        <Layout.AnnotatedSection
          id="company-branding"
          title="Company branding"
          description="Shown on local shipping labels as your company identity."
        >
          <LegacyCard sectioned>
            <FormLayout>
              <TextField
                label="Company name"
                value={form.company_name}
                onChange={(value) => updateField('company_name', value)}
                autoComplete="organization"
              />
              <TextField
                label="Website"
                value={form.website}
                onChange={(value) => updateField('website', value)}
                autoComplete="url"
                placeholder="https://example.com"
              />
              <div>
                <Text variant="bodyMd" as="p" fontWeight="semibold">Company logo</Text>
                <div style={{ marginTop: '8px' }}>
                  <DropZone
                    accept="image/*"
                    type="image"
                    allowMultiple={false}
                    onDrop={handleLogoDrop}
                  >
                    {logoPreview ? (
                      <div style={{ padding: '16px', display: 'flex', alignItems: 'center', gap: '12px' }}>
                        <Thumbnail source={logoPreview} alt="Company logo" size="large" />
                        <Text as="span" variant="bodySm">
                          {logoFile ? logoFile.name : 'Current logo'}
                        </Text>
                      </div>
                    ) : (
                      <DropZone.FileUpload actionHint="Accepts images up to 2MB" />
                    )}
                  </DropZone>
                </div>
                {logoPreview ? (
                  <div style={{ marginTop: '8px' }}>
                    <Button plain destructive onClick={handleRemoveLogo}>
                      Remove logo
                    </Button>
                  </div>
                ) : null}
              </div>
            </FormLayout>
          </LegacyCard>
        </Layout.AnnotatedSection>

        <Layout.AnnotatedSection
          id="contact-information"
          title="Contact information"
          description="We'll use this information on labels so customers can reach you."
        >
          <LegacyCard sectioned>
            <FormLayout>
              <TextField
                label="Business email"
                type="email"
                value={form.email}
                onChange={(value) => updateField('email', value)}
                autoComplete="email"
                helpText="Printed on labels when the template includes an email field."
              />
              <TextField
                label="Phone number"
                value={form.phone}
                onChange={(value) => updateField('phone', value)}
                autoComplete="tel"
                helpText="Shown as the ship-from contact phone on labels."
              />
            </FormLayout>
          </LegacyCard>
        </Layout.AnnotatedSection>

        <Layout.AnnotatedSection
          id="ship-from-address"
          title="Default ship-from address"
          description="Choose your store address or an active Shopify location. You can still edit the fields as overrides."
        >
          <LegacyCard sectioned>
            <FormLayout>
              <Select
                label="Address source"
                options={addressSelectOptions}
                value={selectedAddressValue}
                onChange={handleAddressSelect}
              />
              <TextField
                label="Address 1"
                value={form.address1}
                onChange={(value) => updateField('address1', value)}
                autoComplete="address-line1"
              />
              <TextField
                label="Address 2"
                value={form.address2}
                onChange={(value) => updateField('address2', value)}
                autoComplete="address-line2"
              />
              <FormLayout.Group>
                <TextField
                  label="City"
                  value={form.city}
                  onChange={(value) => updateField('city', value)}
                  autoComplete="address-level2"
                />
                <TextField
                  label="State / Province"
                  value={form.state}
                  onChange={(value) => updateField('state', value)}
                  autoComplete="address-level1"
                />
              </FormLayout.Group>
              <FormLayout.Group>
                <TextField
                  label="ZIP / Postal code"
                  value={form.zip}
                  onChange={(value) => updateField('zip', value)}
                  autoComplete="postal-code"
                />
                <TextField
                  label="Country"
                  value={form.country}
                  onChange={(value) => updateField('country', value)}
                  autoComplete="country"
                />
              </FormLayout.Group>
            </FormLayout>
          </LegacyCard>
        </Layout.AnnotatedSection>

        <Layout.AnnotatedSection
          id="label-defaults"
          title="Label defaults"
          description="Defaults applied when purchasing local shipping labels."
        >
          <LegacyCard sectioned>
            <FormLayout>
              <Select
                label="Default label template"
                options={templates}
                value={form.default_template_id}
                onChange={(value) => updateField('default_template_id', value)}
              />
              <Checkbox
                label="Include barcode on labels"
                checked={form.include_barcode}
                onChange={(value) => updateField('include_barcode', value)}
              />
            </FormLayout>
          </LegacyCard>
        </Layout.AnnotatedSection>
      </Layout>
    </Page>
  );
};

export default LabelSettingsPage;
