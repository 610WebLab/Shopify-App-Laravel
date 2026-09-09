import React, { useCallback, useEffect, useState } from 'react';
import {
  Banner,
  Badge,
  Box,
  Button,
  EmptyState,
  HorizontalStack,
  IndexTable,
  LegacyCard,
  Spinner,
  Text,
  VerticalStack,
} from '@shopify/polaris';
import { useToast } from '@shopify/app-bridge-react';

const StoreLocationsPage = () => {
  const { show } = useToast();
  const [loading, setLoading] = useState(true);
  const [syncing, setSyncing] = useState(false);
  const [locations, setLocations] = useState([]);
  const [error, setError] = useState('');

  const loadLocations = useCallback(async () => {
    setLoading(true);
    setError('');

    try {
      const response = await fetch(`/store-locations?shop=${Config.shop}&include_inactive=1`);
      const result = await response.json();

      if (!result.status) {
        throw new Error(result.message || 'Failed to load store locations');
      }

      setLocations(Array.isArray(result.data) ? result.data : []);
    } catch (err) {
      const message = err.message || 'Failed to load store locations';
      setError(message);
      show(message, { duration: 2500, isError: true });
    } finally {
      setLoading(false);
    }
  }, [show]);

  useEffect(() => {
    loadLocations();
  }, [loadLocations]);

  const handleSync = useCallback(async () => {
    setSyncing(true);
    setError('');

    try {
      const response = await fetch(`/store-locations/sync?shop=${Config.shop}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': Config.csrf_token,
        },
        body: JSON.stringify({
          shop: Config.shop,
          _token: Config.csrf_token,
        }),
      });

      const result = await response.json();
      if (!result.status) {
        throw new Error(result.message || 'Failed to sync store locations');
      }

      setLocations(Array.isArray(result.data) ? result.data : []);
      show(result.message || 'Store locations synced successfully', { duration: 2000 });
    } catch (err) {
      const message = err.message || 'Failed to sync store locations';
      setError(message);
      show(message, { duration: 2500, isError: true });
    } finally {
      setSyncing(false);
    }
  }, [show]);

  const formatAddress = (location) => {
    const parts = [
      location.address1,
      location.city,
      location.province_code || location.province,
      location.zip,
      location.country_code || location.country,
    ].filter(Boolean);

    return parts.length ? parts.join(', ') : '—';
  };

  const rowMarkup = locations.map((location, index) => (
    <IndexTable.Row id={String(location.id)} key={location.id} position={index}>
      <IndexTable.Cell>
        <Text as="span" variant="bodyMd" fontWeight="semibold">
          {location.name || 'Unnamed location'}
        </Text>
      </IndexTable.Cell>
      <IndexTable.Cell>{formatAddress(location)}</IndexTable.Cell>
      <IndexTable.Cell>
        {location.latitude != null && location.longitude != null
          ? `${location.latitude}, ${location.longitude}`
          : '—'}
      </IndexTable.Cell>
      <IndexTable.Cell>
        {location.is_active ? (
          <Badge tone="success">Active</Badge>
        ) : (
          <Badge>Inactive</Badge>
        )}
      </IndexTable.Cell>
    </IndexTable.Row>
  ));

  return (
    <div className="store-locations-settings">
      <VerticalStack gap="4">
        <HorizontalStack align="space-between" blockAlign="center" wrap gap="3">
          <VerticalStack gap="1">
            <Text as="h2" variant="headingMd">
              Store locations
            </Text>
            <Text as="p" variant="bodySm" tone="subdued">
              Saved Shopify locations used when creating shipping zones.
            </Text>
          </VerticalStack>
          <Button primary loading={syncing} onClick={handleSync}>
            Sync from Shopify
          </Button>
        </HorizontalStack>

        {error ? (
          <Banner status="critical" onDismiss={() => setError('')}>
            {error}
          </Banner>
        ) : null}

        <Banner status="info">
          Sync pulls active Shopify locations into this app. Shipping zones can then select a saved location from the dropdown.
        </Banner>

        <LegacyCard>
          {loading ? (
            <Box padding="500">
              <div style={{ display: 'flex', justifyContent: 'center' }}>
                <Spinner accessibilityLabel="Loading store locations" size="large" />
              </div>
            </Box>
          ) : locations.length === 0 ? (
            <EmptyState
              heading="No store locations saved yet"
              action={{
                content: 'Sync from Shopify',
                onAction: handleSync,
                loading: syncing,
              }}
              image="https://cdn.shopify.com/s/files/1/0262/4071/2726/files/emptystate-files.png"
            >
              <p>Import your active Shopify locations so they can be used on shipping zones.</p>
            </EmptyState>
          ) : (
            <IndexTable
              resourceName={{ singular: 'location', plural: 'locations' }}
              itemCount={locations.length}
              headings={[
                { title: 'Name' },
                { title: 'Address' },
                { title: 'Coordinates' },
                { title: 'Status' },
              ]}
              selectable={false}
            >
              {rowMarkup}
            </IndexTable>
          )}
        </LegacyCard>
      </VerticalStack>
    </div>
  );
};

export default StoreLocationsPage;
