import axios from 'axios';
import { useState, useEffect } from 'react';
import {
  Page,
  Layout,
  Card,
  TextField,
  Button,
  Modal,
  IndexTable,
  useIndexResourceState,
  Badge,
} from '@shopify/polaris';
import { Document, Page as PdfPage } from 'react-pdf';
import { confirm } from 'react-confirm-box';

const LabelTemplateManager = () => {
  const [templates, setTemplates] = useState([]);
  const [formState, setFormState] = useState({ name: '', content: '' });
  const [pdfUrl, setPdfUrl] = useState(null);
  const [previewModalActive, setPreviewModalActive] = useState(false);
  const [editingTemplate, setEditingTemplate] = useState(null);

  const { selectedResources, allResourcesSelected, handleSelectionChange } = useIndexResourceState(templates);

  useEffect(() => {
    fetchTemplates();
  }, []);

  const fetchTemplates = async () => {
    try {
      const { data } = await axios.get('/label-templates');
      setTemplates(data);
    } catch (error) {
      console.error('Error fetching templates:', error);
    }
  };

  const handleInputChange = (field) => (value) => {
    setFormState((prev) => ({ ...prev, [field]: value }));
  };

  const createOrUpdateTemplate = async () => {
    if (!formState.name.trim() || !formState.content.trim()) {
      alert('Template name and content are required.');
      return;
    }

    const endpoint = editingTemplate ? `/label-templates/${editingTemplate.id}` : '/label-templates';
    const method = editingTemplate ? 'put' : 'post';

    try {
      const { data } = await axios[method](endpoint, formState);
      setTemplates((prevTemplates) =>
        editingTemplate
          ? prevTemplates.map((template) => (template.id === editingTemplate.id ? data : template))
          : [...prevTemplates, data]
      );
      resetForm();
    } catch (error) {
      console.error('Error saving template:', error);
      alert('An error occurred while saving the template.');
    }
  };

  const editTemplate = (template) => {
    setFormState({ name: template.name, content: template.content });
    setEditingTemplate(template);
  };

  const deleteTemplate = async (id) => {
    const confirmed = window.confirm('Are you sure you want to delete this template?');

    if (!confirmed) return;

    try {
      await axios.delete(`/label-templates/${id}`);
      setTemplates((prevTemplates) => prevTemplates.filter((template) => template.id !== id));
    } catch (error) {
      console.error('Error deleting template:', error);
      alert('Failed to delete the template.');
    }
  };

  const generatePdf = async (id) => {
    try {
      const response = await fetch(`/label-templates/${id}/generate-pdf`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
      });

      if (!response.ok) throw new Error('Network response was not ok');

      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `label_template_${id}.pdf`);
      document.body.appendChild(link);
      link.click();
      link.parentNode.removeChild(link);
    } catch (error) {
      console.error('Error downloading the PDF:', error);
      alert('Failed to download the PDF.');
    }
  };

  const resetForm = () => {
    setFormState({ name: '', content: '' });
    setEditingTemplate(null);
  };

  const resourceName = { singular: 'template', plural: 'templates' };

  const rowMarkup = templates.map((template, index) => (
    <IndexTable.Row
      id={template.id.toString()}
      key={template.id}
      selected={selectedResources.includes(template.id.toString())}
      position={index}
    >
      <IndexTable.Cell>{template.name}</IndexTable.Cell>
      <IndexTable.Cell>
        <div style={{ display: 'flex', gap: '8px' }}>
          <Button size="slim" onClick={() => generatePdf(template.id)}>Preview</Button>
          <Button size="slim" onClick={() => editTemplate(template)}>Edit</Button>
          <Button size="slim" destructive onClick={() => deleteTemplate(template.id)}>Delete</Button>
        </div>
      </IndexTable.Cell>
    </IndexTable.Row>
  ));

  return (
    <Page title="Label Template Management" fullWidth>
      <Layout>
        <Layout.Section>
          <Card sectioned title={editingTemplate ? 'Edit Template' : 'Create New Template'}>
            <TextField label="Template Name" value={formState.name} onChange={handleInputChange('name')} placeholder="Enter template name" />
            <TextField
              label="HTML Content"
              value={formState.content}
              onChange={handleInputChange('content')}
              multiline={6}
              placeholder="HTML with placeholders: {order_name}, {order_number}, {item_number}, {price}, {qr_code}"
            />
            <div style={{ marginTop: '16px', display: 'flex', gap: '8px' }}>
              <Button primary onClick={createOrUpdateTemplate}>
                {editingTemplate ? 'Update Template' : 'Create Template'}
              </Button>
              {editingTemplate && <Button onClick={resetForm}>Cancel Edit</Button>}
            </div>
          </Card>
        </Layout.Section>

        <Layout.Section>
          <Card title="Saved Templates">
            <IndexTable
              resourceName={resourceName}
              itemCount={templates.length}
              selectedItemsCount={allResourcesSelected ? 'All' : selectedResources.length}
              onSelectionChange={handleSelectionChange}
              headings={[{ title: 'Name' }, { title: 'Actions' }]}
            >
              {rowMarkup}
            </IndexTable>
          </Card>
        </Layout.Section>

        {pdfUrl && (
          <Modal open={previewModalActive} onClose={() => setPreviewModalActive(false)} title="PDF Preview" large>
            <Modal.Section>
              <div style={{ border: '1px solid #dfe3e8', borderRadius: 4, padding: 16 }}>
                <Document file={pdfUrl}>
                  <PdfPage pageNumber={1} />
                </Document>
              </div>
            </Modal.Section>
          </Modal>
        )}
      </Layout>
    </Page>
  );
};

export default LabelTemplateManager;
