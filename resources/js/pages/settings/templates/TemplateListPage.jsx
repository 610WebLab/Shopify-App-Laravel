import axios from 'axios';
import React, { useCallback, useEffect, useState } from 'react';
import {
  Page,
  Layout,
  LegacyCard,
  Button,
  ButtonGroup,
  IndexTable,
  useIndexResourceState,
  Modal,
  TextContainer,
  Spinner,
  Badge,
  Text,
  EmptyState,
  Box,
  Banner,
} from '@shopify/polaris';
import { useNavigate } from 'react-router-dom';
import { useToast } from '@shopify/app-bridge-react';

const TemplateListPage = () => {
  const [loading, setLoading] = useState(true);
  const [templates, setTemplates] = useState([]);
  const [activeDeleteModal, setActiveDeleteModal] = useState(false);
  const [selectedTemplate, setSelectedTemplate] = useState(null);
  const [previewingId, setPreviewingId] = useState(null);
  const [deleting, setDeleting] = useState(false);
  const { selectedResources, allResourcesSelected, handleSelectionChange } = useIndexResourceState(templates);
  const navigate = useNavigate();
  const { show } = useToast();

  const fetchTemplates = useCallback(async () => {
    setLoading(true);
    try {
      const { data } = await axios.get(`/label-templates?shop=${Config.shop}`);
      setTemplates(Array.isArray(data) ? data : []);
    } catch (error) {
      show('Failed to load label templates.', { duration: 2500, isError: true });
    } finally {
      setLoading(false);
    }
  }, [show]);

  useEffect(() => {
    fetchTemplates();
  }, [fetchTemplates]);

  const handleDelete = useCallback(async () => {
    if (!selectedTemplate) {
      return;
    }

    setDeleting(true);
    try {
      await axios.delete(`/label-templates/${selectedTemplate.id}?shop=${Config.shop}`);
      setActiveDeleteModal(false);
      setSelectedTemplate(null);
      show('Template deleted successfully.', { duration: 2000 });
      fetchTemplates();
    } catch (error) {
      show('Unable to delete this template.', { duration: 2500, isError: true });
    } finally {
      setDeleting(false);
    }
  }, [fetchTemplates, selectedTemplate, show]);

  const handlePreview = useCallback(async (template) => {
    setPreviewingId(template.id);
    try {
      const response = await fetch(`/label-templates/${template.id}/generate-pdf?shop=${Config.shop}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/pdf',
          'X-CSRF-TOKEN': Config.csrf_token,
        },
      });

      if (!response.ok) {
        let message = 'Failed to generate label preview.';
        try {
          const errorBody = await response.json();
          message = errorBody.message || message;
        } catch (parseError) {
          // Keep default message when response is not JSON.
        }
        throw new Error(message);
      }

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const orderLabel = response.headers.get('X-Preview-Order');
      const newTab = window.open(url, '_blank');

      if (!newTab) {
        show('Please allow popups to view the label preview.', { duration: 3000, isError: true });
      } else {
        show(
          orderLabel
            ? `Preview ready using order ${orderLabel}.`
            : 'Preview ready using your latest order data.',
          { duration: 2500 }
        );
      }

      setTimeout(() => window.URL.revokeObjectURL(url), 60000);
    } catch (error) {
      show(error.message || 'Failed to preview the PDF.', { duration: 2500, isError: true });
    } finally {
      setPreviewingId(null);
    }
  }, [show]);

  const rowMarkup = templates.map((template, index) => {
    const templateId = String(template.id);
    const isDefault = template.type === 'default';
    const isPreviewing = previewingId === template.id;

    return (
      <IndexTable.Row
        id={templateId}
        key={template.id}
        selected={selectedResources.includes(templateId)}
        position={index}
      >
        <IndexTable.Cell>
          <div className="label-template-name-cell">
            <Text as="span" variant="bodyMd" fontWeight="semibold">
              {template.name}
            </Text>
            {isDefault ? (
              <Badge tone="info">Default</Badge>
            ) : (
              <Badge>Custom</Badge>
            )}
          </div>
        </IndexTable.Cell>
        <IndexTable.Cell>
          <ButtonGroup>
            <Button
              size="slim"
              onClick={() => navigate(`/pages/templates/edit/${template.id}`)}
            >
              Edit
            </Button>
            <Button
              size="slim"
              loading={isPreviewing}
              onClick={() => handlePreview(template)}
            >
              Preview
            </Button>
            <Button
              size="slim"
              tone="critical"
              disabled={isDefault}
              onClick={() => {
                setSelectedTemplate(template);
                setActiveDeleteModal(true);
              }}
            >
              Delete
            </Button>
          </ButtonGroup>
        </IndexTable.Cell>
      </IndexTable.Row>
    );
  });

  return (
    <Page
      title="Label Templates"
      subtitle="Design shipping labels and preview them with your latest order"
      secondaryActions={[
        {
          content: 'Label Settings',
          onAction: () => navigate('/pages/templates/label-settings'),
        },
      ]}
      primaryAction={{
        content: 'Create Template',
        onAction: () => navigate('/pages/templates/create'),
      }}
    >
      <div className="label-templates-list">
        <Layout>
          <Layout.Section>
            <Banner status="info">
              Preview fills every template variable from Label Settings and your most recent Shopify order.
            </Banner>
          </Layout.Section>

          <Layout.Section>
            <LegacyCard>
              {loading ? (
                <Box padding="500">
                  <div className="label-templates-loading">
                    <Spinner accessibilityLabel="Loading label templates" size="large" />
                  </div>
                </Box>
              ) : templates.length === 0 ? (
                <EmptyState
                  heading="Create your first label template"
                  action={{
                    content: 'Create Template',
                    onAction: () => navigate('/pages/templates/create'),
                  }}
                  secondaryAction={{
                    content: 'Label Settings',
                    onAction: () => navigate('/pages/templates/label-settings'),
                  }}
                  image="https://cdn.shopify.com/s/files/1/0262/4071/2726/files/emptystate-files.png"
                >
                  <p>Build enterprise shipping labels with branding, addresses, barcodes, and order data.</p>
                </EmptyState>
              ) : (
                <IndexTable
                  resourceName={{ singular: 'template', plural: 'templates' }}
                  itemCount={templates.length}
                  selectedItemsCount={allResourcesSelected ? 'All' : selectedResources.length}
                  onSelectionChange={handleSelectionChange}
                  headings={[
                    { title: 'Name' },
                    { title: 'Actions' },
                  ]}
                  selectable
                >
                  {rowMarkup}
                </IndexTable>
              )}
            </LegacyCard>
          </Layout.Section>
        </Layout>
      </div>

      <Modal
        open={activeDeleteModal}
        onClose={() => setActiveDeleteModal(false)}
        title="Delete template"
        primaryAction={{
          content: 'Delete',
          tone: 'critical',
          loading: deleting,
          onAction: handleDelete,
        }}
        secondaryActions={[
          {
            content: 'Cancel',
            onAction: () => setActiveDeleteModal(false),
          },
        ]}
      >
        <Modal.Section>
          <TextContainer>
            Delete “{selectedTemplate?.name}”? This cannot be undone.
          </TextContainer>
        </Modal.Section>
      </Modal>
    </Page>
  );
};

export default TemplateListPage;
