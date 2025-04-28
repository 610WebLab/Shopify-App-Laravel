import React,{ useState, useEffect, useCallback } from 'react';
import {
  Form,
  FormLayout,
  TextField,
  Button,
  Card,
  Select,
  Toast,
  IndexTable,
  LegacyCard,
  Badge,
  Text,
  Layout
} from '@shopify/polaris';
import { useLocation, Link, useNavigate, useParams } from "react-router-dom";
import CarrierStatusCheckBox from './CarrierStatusCheckBox';
export default function AddEasyPost({ selected, setSelected, id }) {

  const [apiKey, setApiKey] = useState('');
  const params = useParams();
  const navigate = useNavigate();
  const [carrierServiceId, setCarrierServiceId] = useState(id);
  const [carriers, setCarriers] = useState([]);

  const statusOptions = [
    { label: 'Active', value: 'active' },
    { label: 'Inactive', value: 'inactive' },
  ];

  const [status, setStatus] = useState('active');

  // State to hold inline field error for API Key
  const [apiKeyError, setApiKeyError] = useState('');

  // State for controlling the Toast component
  const [toastActive, setToastActive] = useState(false);
  const [toastContent, setToastContent] = useState('');
  const [toastError, setToastError] = useState(false);

  // Function to toggle (dismiss) the toast
  const toggleToast = useCallback(() => {
    setToastActive(false);
  }, []);
  const fetchCarriers = async (id) => {
    try {
      const response = await fetch(`/carrier/${id}?shop=${Config.shop}`);
      const result = await response.json();
      if (result.status) {
        setCarriers(result.data);
      }
    } catch (error) {
      console.error("Error fetching carriers:", error);
    }
  };
  useEffect(() => {
    if (carrierServiceId > 0) {
      fetchCarriers(carrierServiceId)

    }
  }, [carrierServiceId])

  const handleSubmit = useCallback(async () => {
    setToastActive(false);
    if (apiKey.trim() === '') {
      setApiKeyError('API Key is required.');
      setToastContent('API Key is required.');
      setToastError(true);
      setToastActive(true);
      return; // Prevent form submission
    } else {
      setApiKeyError('');
    }

    // Create the form data object
    const formData = {
      api_key: apiKey,
      status: status,
      service_type: selected, // using the prop passed in
    };
    // Build the URL. Ensure that Config.shop is defined and available in your project.
    const url = !id ? `/other-carrier-service?shop=${Config.shop}` : `/other-carrier-service/${id}?shop=${Config.shop}`;
    const method = !id ? 'POST' : 'PUT';

    try {
      const response = await fetch(url, {
        method: method, // define the method as POST
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData),
      });

      const result = await response.json();
      if (result.status) {
        setToastContent(result.message);
        setCarrierServiceId(result.data.id);
        setSelected(result.data.carrier_type)
        setToastError(false);
        setToastActive(true);
        setTimeout(() => {
          navigate('/pages/other-careers');
        }, 2000);
      } else {
        setToastContent(result.message || 'An unknown error occurred.');
        setToastError(true);
        setToastActive(true);
      }

      // if (response.ok) {

      //   setToastContent(id?'Carrier service updated successfully.':'Carrier created successfully.');
      //   setToastError(false);
      //   setToastActive(true);
      // } else {
      //   setToastContent(responseData.error || 'An unknown error occurred.');
      //   setToastError(true);
      //   setToastActive(true);
      // }
    } catch (error) {
      console.error('Error saving carrier:', error);
      setToastContent('An error occurred while saving the carrier.');
      setToastError(true);
      setToastActive(true);
    }
  }, [apiKey, status, selected]);

  const getCarrierService = async (id) => {
    try {
      const response = await fetch(`/other-carrier-service/${id}/edit`);
      const data = await response.json();
      console.log('datadata', data, data?.data?.api_key)

      if (data.success) {
        setApiKey(data?.data?.api_key)
        setStatus(data?.data?.status)
      }
    } catch (error) {
      console.error("Error fetching carriers:", error);
    }
  }

  useEffect(() => {
    if (id) {
      getCarrierService(id)
    }
  }, [id])

  const maskApiKey = (apiKey) => {
    if (!apiKey) return '';
    const len = apiKey.length;
    // If the API key is very short, return it unmasked
    if (len <= 4) return apiKey;
    return 'x'.repeat(len - 4) + apiKey.slice(len - 4);
  };

  const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const yyyy = date.getFullYear();
    return `${dd}-${mm}-${yyyy}`;
  }
  const resourceName = {
    singular: 'carrier',
    plural: 'carriers',
  };

  const rowMarkup = carriers.length > 0 && carriers.map(
    (
      { id, service_id, name, account_id, status, created_at },
      index,
    ) => (
      <IndexTable.Row
        id={id}
        key={id}
        // selected={selectedResources.includes(id)}
        position={index}
      >
        <IndexTable.Cell>{name}</IndexTable.Cell>
        <IndexTable.Cell>{account_id ? maskApiKey(account_id) : ''}</IndexTable.Cell>
        <IndexTable.Cell>
          {/* {status == "active" ? <Badge tone="success">Active</Badge> : <Badge tone="critical">Inactive</Badge>} */}
          <CarrierStatusCheckBox
            defaultValue={status == "active" ? true : false}
            methodId={id}
          />
        </IndexTable.Cell>
        <IndexTable.Cell>{formatDate(created_at)}</IndexTable.Cell>
        {/* <IndexTable.Cell>
          <div className="shipping-zone-btn-action">
            <Button plain onClick={() => {console.log("Edit triggred")}}>Edit</Button>&nbsp;
            <Button plain destructive onClick={() => {console.log("Delete triggred")}}>Delete</Button>
          </div>
        </IndexTable.Cell>*/}
      </IndexTable.Row>
    ),
  );


  return (
    <>
    <div className='services-page-opt'>
      <Card>
        <Form onSubmit={handleSubmit}>
          <FormLayout>
            {/* API Key Input */}
            <TextField
              value={apiKey}
              onChange={setApiKey}
              label="API Key"
              type="text"
              error={apiKeyError}
              requiredIndicator
            />

            {/* Status Select */}
            <Select
              label="Status"
              options={statusOptions}
              onChange={setStatus}
              value={status}
            />
            <Button submit primary>
              Submit
            </Button>
          </FormLayout>
        </Form>
      </Card>
    </div>
      <Layout>
        <Layout.Section>
          <LegacyCard>
            <LegacyCard.Section>
              <Text variant="headingLg" as="h3">
                Carrier Service
              </Text>
            </LegacyCard.Section>
            <LegacyCard.Section>
            <LegacyCard>
              <div className='tc-index-table'>
                <IndexTable
                  resourceName={resourceName}
                  itemCount={carriers.length}
                  headings={[
                    { title: 'Name' },
                    { title: 'Account Id' },
                    { title: 'Status' },
                    { title: 'Date' },
                  ]}
                  selectable={false}
                >
                  {rowMarkup}
                </IndexTable>
              </div>
            </LegacyCard>
            </LegacyCard.Section>
          </LegacyCard>
          {/* </Card> */}
        </Layout.Section>
      </Layout>
      {toastActive && (
        <Toast
          content={toastContent}
          error={toastError}
          onDismiss={toggleToast}
        />
      )}
    </>
  );
}
