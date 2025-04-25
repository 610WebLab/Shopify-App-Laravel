import React, { useEffect, useState } from 'react'
import {
  Page,
  IndexTable,
  LegacyCard,
  useIndexResourceState,
  Text,
  Toast,
  Modal,
  Button,
  Frame
} from '@shopify/polaris';
import { useNavigate } from 'react-router-dom';
import { useToast } from '@shopify/app-bridge-react';
import CheckBoxOtherCarrierServices from './CheckBoxOtherCarrierServices';
export default function ListingOtherCarrierServices() {
  const navigate = useNavigate();
  const { show } = useToast();
  const [serviceCarriers, setServiceCarriers] = useState([])
  const [deleteCarrierId, setDeleteCarrierId] = useState(null); // Store carrier id to be deleted
  const [toastMessage, setToastMessage] = useState("");

  const fetchServiceCarriers = async () => {
    try {
      const response = await fetch(`/other-carrier-service?shop=${Config.shop}`);
      const data = await response.json();
      setServiceCarriers(data);
    } catch (error) {
      console.error("Error fetching carriers:", error);
    }
  };

  useEffect(() => {
    fetchServiceCarriers()

  }, [])
  const handleDeleteCarrier = async () => {
    try {
      const response = await fetch(`/other-carrier-service/${deleteCarrierId}`, {
        method: 'DELETE',
      });
      const result = await response.json();
      if (result.status) {
        show(result.message, { duration: 2000 });
        fetchServiceCarriers(); // Refresh the carrier list after deletion
        setDeleteCarrierId(null);
      } else {
        show(result.message, { duration: 2000, isError: true });
      }
    } catch (error) {
      console.error("Error deleting carrier:", error);
    }
  };
  const maskApiKey = (apiKey) => {
    if (!apiKey) return '';
    const len = apiKey.length;
    // If the API key is very short, return it unmasked
    if (len <= 4) return apiKey;
    return 'x'.repeat(len - 4) + apiKey.slice(len - 4);
  };

  // Helper function to format date as dd-mm-yyyy
  const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const yyyy = date.getFullYear();
    return `${dd}-${mm}-${yyyy}`;
  };

  const resourceName = {
    singular: 'service Carrier',
    plural: 'service Carriers',
  };

  const { selectedResources, allResourcesSelected, handleSelectionChange } =
    useIndexResourceState(serviceCarriers);

  const rowMarkup = serviceCarriers.map(
    (
      { id, carrier_type, api_key, status, created_at },
      index,
    ) => (
      <IndexTable.Row
        id={id}
        key={id}
        selected={selectedResources.includes(id)}
        position={index}
        onClick={() => { console.log('Row Click'); }}
      >
        <IndexTable.Cell>
          {carrier_type ? carrier_type.charAt(0).toUpperCase() + carrier_type.slice(1): ""}
          <div className="shipping-zone-btn-action">
            <Button plain onClick={() => navigate(`/pages/update-other-careers/${id}/${carrier_type}`)}>Edit</Button>&nbsp;
            <Button plain destructive onClick={() => setDeleteCarrierId(id)}>Delete</Button>
          </div>
        </IndexTable.Cell>
        <IndexTable.Cell>{api_key ? maskApiKey(api_key) : ''}</IndexTable.Cell>
        <IndexTable.Cell>
          {/* {status == "active" ? <Badge tone="success">Active</Badge> : <Badge tone="critical">Inactive</Badge>} */}

          <CheckBoxOtherCarrierServices
            defaultValue={status == "active" ? true : false}
            methodId={id}
          />
        </IndexTable.Cell>
        <IndexTable.Cell>{formatDate(created_at)}</IndexTable.Cell>

      </IndexTable.Row>
    ),
  );

  return (
    <Page
      backAction={{ content: 'Products', url: '#' }}
      title="Carrier Services Listing"
      primaryAction={{
        content: 'Add Carrier Services',
        onAction: () => navigate('/pages/add-other-careers'), // Redirect on click
      }}
    >
      <LegacyCard>
        <IndexTable
          resourceName={resourceName}
          itemCount={serviceCarriers.length}
          selectedItemsCount={
            allResourcesSelected ? 'All' : selectedResources.length
          }
          onSelectionChange={handleSelectionChange}
          headings={[
            { title: 'Name' },
            { title: 'Api key' },
            { title: 'Status' },
            { title: 'Date' },
          ]}
          selectable={false}
        >
          {rowMarkup}
        </IndexTable>
        {deleteCarrierId && (
          <Modal
            open={true}
            onClose={() => setDeleteCarrierId(null)}
            title="Delete Carrier"
            primaryAction={{
              content: 'Delete',
              destructive: true,
              onAction: handleDeleteCarrier,
            }}
            secondaryActions={[
              {
                content: 'Cancel',
                onAction: () => setDeleteCarrierId(null),
              },
            ]}>
            <Modal.Section>
              <Text variant="bodyMd">
                Are you sure you want to delete this carrier?
              </Text>
            </Modal.Section>
          </Modal>
        )}

        {/* Toast Notification */}
        {toastMessage && (
          <Frame>
            <Toast
              error={toastMessage?.error}
              content={Array.isArray(toastMessage.message) ? (
                <ul>
                  {toastMessage.message.map((msg, index) => (
                    <li key={index}>{msg}</li>
                  ))}
                </ul>
              ) : (
                <p>{toastMessage.message}</p>
              )}
              onDismiss={() => setToastMessage('')}
            />
          </Frame>
        )}
      </LegacyCard>
    </Page>
  )
}
