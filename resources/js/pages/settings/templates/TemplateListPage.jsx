// TemplateListPage.js
import axios from 'axios';
import { useState, useEffect } from 'react';
import {
  Page,
  Layout,
  Card,
  Button,
  IndexTable,
  useIndexResourceState,
  Modal,
  TextContainer,
  Spinner
} from '@shopify/polaris';
import { useNavigate } from 'react-router-dom';

const TemplateListPage = () => {
  const [loading, setLoading] = useState(false);

  const [templates, setTemplates] = useState([]);
  const [activeDeleteModal, setActiveDeleteModal] = useState(false);
  const [selectedTemplate, setSelectedTemplate] = useState(null);
  const { selectedResources, allResourcesSelected, handleSelectionChange } = useIndexResourceState(templates);
  const navigate = useNavigate();

  useEffect(() => {
    fetchTemplates();
  }, []);

  const fetchTemplates = async () => {
    setLoading(true);
    try {
      
      const { data } = await axios.get(`/label-templates?shop=${Config.shop}`);
      setTemplates(data);
    } catch (error) {
      console.error('Error fetching templates:', error);
    }finally {
      setLoading(false);
    }
  };

  const handleDelete = async () => {
    if (!selectedTemplate) return;
    try {
      await axios.delete(`/label-templates/${selectedTemplate.id}`);
      setActiveDeleteModal(false);
      setSelectedTemplate(null);
      fetchTemplates();
    } catch (error) {
      console.error('Error deleting template:', error);
      alert('An error occurred while deleting the template.');
    }
  };

  // const handlePreview = (template) => {
  //   alert(`Preview Template: ${template.name}\n\n${template.content}`);
  // };
  const handlePreview = async (id) => {
    try {
        const response = await fetch(`/label-templates/${id}/generate-pdf`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
        });

        if (!response.ok) throw new Error('Network response was not ok');

        // Convert response to Blob
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);

        // Open in a new tab for print preview
        const newTab = window.open(url, '_blank');
        if (!newTab) {
            alert('Please allow popups for this website to preview the PDF.');
        }
    } catch (error) {
        console.error('Error previewing the PDF:', error);
        alert('Failed to preview the PDF.');
    }
};

  const rowMarkup = templates.map((template, index) => (
    <IndexTable.Row
      id={template.id.toString()}
      key={template.id}
      selected={selectedResources.includes(template.id.toString())}
      position={index}
      onClick={() => { console.log("Row Click"); }}
    >
      <IndexTable.Cell>{template.name}</IndexTable.Cell>
      <IndexTable.Cell>
        <Button size="slim" onClick={() => navigate(`/pages/templates/edit/${template.id}`)}>Edit</Button>
        <Button size="slim" onClick={() => handlePreview(template.id)} tone="secondary">Preview</Button>
        <Button
          size="slim"
          tone="critical"
          onClick={() => {
            setSelectedTemplate(template);
            setActiveDeleteModal(true);
          }}
          disabled={template.type =="default"}
          style={{ cursor: template.type != "default" ? 'pointer' : 'not-allowed' }}
        >
          Delete
        </Button>
      </IndexTable.Cell>
    </IndexTable.Row>
  ));

  return (
    <Page
      title="Label Templates"
      primaryAction={{ content: 'Create Template', onAction: () => navigate('/pages/templates/create') }}
    >
      <div className="template_list">
      <Layout>
        <Layout.Section>
          <Card title="Saved Templates">
            {loading ?<Spinner accessibilityLabel="Spinner example" size="large" />: <IndexTable
              resourceName={{ singular: 'template', plural: 'templates' }}
              itemCount={templates.length}
              selectedItemsCount={allResourcesSelected ? 'All' : selectedResources.length}
              onSelectionChange={handleSelectionChange}
              headings={[{ title: 'Name' }, { title: 'Actions' }]}
            >
              {rowMarkup}
            </IndexTable>}
          </Card>
        </Layout.Section>
      </Layout>
      </div>
      {activeDeleteModal && (
        <Modal
          open={activeDeleteModal}
          onClose={() => setActiveDeleteModal(false)}
          title="Delete Template"
          primaryAction={{ content: 'Delete', tone: 'critical', onAction: handleDelete }}
          secondaryActions={[{ content: 'Cancel', onAction: () => setActiveDeleteModal(false) }]}
        >
          <Modal.Section>
            <TextContainer>
              Are you sure you want to delete the template "{selectedTemplate?.name}"? This action cannot be undone.
            </TextContainer>
          </Modal.Section>
        </Modal>
      )}
    </Page>
  );
};

export default TemplateListPage;
