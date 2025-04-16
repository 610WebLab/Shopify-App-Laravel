import {
    Page,
    Badge,
    IndexTable,
    LegacyCard,
    useIndexResourceState,
    Text,
    Select,
    Button,
    Modal,
    TextField,
    ActionList,
    Frame,
    Toast,
  } from '@shopify/polaris';
  import { useEffect, useState } from 'react';
  
  const API_URL = "/carriers"; // Update your API URL if needed
  
  const OthersCarriers = () => {
    const [carriers, setCarriers] = useState([]);
    const [selectedResources, setSelectedResources] = useState([]);
    const [allResourcesSelected, setAllResourcesSelected] = useState(false);
  
    const [showModal, setShowModal] = useState(false);
    const [editCarrier, setEditCarrier] = useState(null); // Store carrier to be edited
    const [deleteCarrierId, setDeleteCarrierId] = useState(null); // Store carrier id to be deleted
    const [errors, setErrors] = useState({}); // Store validation errors
    const [toastMessage, setToastMessage] = useState(""); // Store toast message
  
    const resourceName = {
      singular: 'carrier',
      plural: 'carriers',
    };
  
    // Fetch carriers from the API
    const fetchCarriers = async () => {
      try {
        const response = await fetch(`/carriers?shop=${Config.shop}`);
        const data = await response.json();
        setCarriers(data);
      } catch (error) {
        console.error("Error fetching carriers:", error);
      }
    };
  
    useEffect(() => {
      fetchCarriers();
    }, []);
      
      console.log('Config',Config.shop)
  
    // Handle selection change in IndexTable
    const handleSelectionChange = (resources) => {
      setSelectedResources(resources);
      setAllResourcesSelected(resources.length === carriers.length);
    };
  
    // Handle status update
    const handleStatusChange = async (id, newStatus) => {
      try {
        const response = await fetch(`/carriers/${id}?shop=${Config.shop}`, {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ status: newStatus }),
        });
  
        if (response.ok) {
          fetchCarriers(); // Refresh the carrier list after updating status
        }
      } catch (error) {
        console.error("Error updating status:", error);
      }
    };
  
    // Open the modal to edit a carrier
    const openEditModal = (carrier) => {
      setEditCarrier(carrier || { carrier_name: '', account_id: '', api_key: '', status: 'active' });
      setShowModal(true);
      setErrors({}); // Reset errors when modal opens
    };
  
    // Close the modal
    const closeModal = () => {
      setShowModal(false);
      setEditCarrier(null);
    };
  
    // Form validation function
    const validateForm = () => {
      const validationErrors = {};
      if (!editCarrier?.carrier_name) validationErrors.carrier_name = "Carrier name is required.";
      if (!editCarrier?.account_id) validationErrors.account_id = "Account ID is required.";
      if (!editCarrier?.api_key) validationErrors.api_key = "API Key is required.";
      setErrors(validationErrors);
      return Object.keys(validationErrors).length === 0; // If no errors, return true
    };
  
    // Handle carrier creation or editing (update)
    const handleSaveCarrier = async () => {
      if (!validateForm()) return; // Validate form before saving
  
      const method = editCarrier.id ? 'PUT' : 'POST';
      const url = editCarrier.id ? `/carriers/${editCarrier.id}?shop=${Config.shop}` :`/carriers?shop=${Config.shop}`;
  
      try {
        const response = await fetch(url, {
          method,
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(editCarrier),
        });
  
        const responseData = await response.json();

        if (response.ok) {
          fetchCarriers();
          setToastMessage({
            error: false,
            message: editCarrier.id
              ? "Carrier updated successfully."
              : "Carrier created successfully.",
          });
          closeModal();
        } else {
            let errorMessage = "An error occurred.";
      
            if (responseData.errors) {
                const errorMessages = Object.values(responseData.errors)
                  .flat()
                  .join(", "); // Join the error messages into a single string
                  
                setToastMessage({
                  error: true,
                  message: errorMessages,
                });
              } else {
                setToastMessage({
                    error: true,
                  message: responseData.message || "An unknown error occurred.",
                });
              }
            }
      } catch (error) {
        console.error("Error saving carrier:", error);
      }
    };
  
    // Handle carrier deletion
    const handleDeleteCarrier = async () => {
      try {
        const response = await fetch(`${API_URL}/${deleteCarrierId}`, {
          method: 'DELETE',
        });
  
        if (response.ok) {
          fetchCarriers(); // Refresh the carrier list after deletion
            setDeleteCarrierId(null);
            setToastMessage({
                error: false,
              message: "Carrier deleted successfully.",
            });
        }
      } catch (error) {
        console.error("Error deleting carrier:", error);
      }
    };
  
    const rowMarkup = carriers?.map(
      ({ id, carrier_name, account_id, api_key, status }, index) => (
        <IndexTable.Row
          id={id}
          key={id}
          selected={selectedResources.includes(id)}
          position={index}
        >
          <IndexTable.Cell>
            <Text variant="bodyMd" fontWeight="bold" as="span">
              {carrier_name}
            </Text>
          </IndexTable.Cell>
          <IndexTable.Cell>{account_id}</IndexTable.Cell>
          <IndexTable.Cell>{api_key}</IndexTable.Cell>
          <IndexTable.Cell>
            <Select
              options={[
                { label: 'Active', value: 'active' },
                { label: 'Inactive', value: 'inactive' },
              ]}
              onChange={(newStatus) => handleStatusChange(id, newStatus)}
              value={status}
            />
          </IndexTable.Cell>
                <IndexTable.Cell>
                    
                <div className="shipping-zone-btn-action">
                        <Button plain onClick={() => openEditModal({ id, carrier_name, account_id, api_key, status })}>Edit</Button>&nbsp;
                        <Button plain destructive onClick={() => setDeleteCarrierId(id)}>Delete</Button>
                </div>
          </IndexTable.Cell>
        </IndexTable.Row>
      )
    );
  
      return (
        <div className='other_carriers_page'>
            <Page
                title="Carriers"
                primaryAction={{
                content: 'Add Carrier',
                onAction: () => openEditModal(null), // Open modal for adding new carrier
                  }}
                  fullWidth
                >
                <LegacyCard>
                    <IndexTable
                    resourceName={resourceName}
                    itemCount={carriers.length}
                    selectedItemsCount={allResourcesSelected ? 'All' : selectedResources.length}
                    onSelectionChange={handleSelectionChange}
                    headings={[
                        { title: 'Carrier Name' },
                        { title: 'Account ID' },
                        { title: 'API Key' },
                        { title: 'Status' },
                        { title: 'Actions' },
                    ]}
                    >
                    {rowMarkup}
                    </IndexTable>
            
                    {/* Edit Carrier Modal */}
                    <Modal
                    open={showModal}
                    onClose={closeModal}
                    title={editCarrier?.id ? "Edit Carrier" : "Add Carrier"}
                    primaryAction={{
                        content: 'Save',
                        onAction: handleSaveCarrier,
                    }}
                    secondaryActions={[
                        {
                        content: 'Cancel',
                        onAction: closeModal,
                        },
                    ]}
                    >
                    <Modal.Section>
                        <TextField
                        label="Carrier Name"
                        value={editCarrier?.carrier_name || ''}
                        onChange={(value) => setEditCarrier({ ...editCarrier, carrier_name: value })}
                        required
                        error={errors.carrier_name}
                        />
                        <TextField
                        label="Account ID"
                        value={editCarrier?.account_id || ''}
                        onChange={(value) => setEditCarrier({ ...editCarrier, account_id: value })}
                        required
                        error={errors.account_id}
                        />
                        <TextField
                        label="API Key"
                        value={editCarrier?.api_key || ''}
                        onChange={(value) => setEditCarrier({ ...editCarrier, api_key: value })}
                        required
                        error={errors.api_key}
                        />
                         <Select
                        label="Status"
                        value={editCarrier?.status || 'active'}
                        options={[
                            { label: 'Active', value: 'active' },
                            { label: 'Inactive', value: 'inactive' },
                        ]}
                        onChange={(newStatus) => setEditCarrier({ ...editCarrier, status: newStatus })}
                        />
                    </Modal.Section>
                    </Modal>
            
                    {/* Delete Confirmation Modal */}
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
                        ]}
                    >
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
                                  error = {toastMessage?.error}
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
        </div>
    );
  };
  
  export default OthersCarriers;
  