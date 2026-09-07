import React, { useState, useEffect, useRef, useCallback, useMemo } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
  Page,
  Layout,
  LegacyCard,
  TextField,
  Badge,
  Spinner,
  Text,
  Banner,
  Box,
} from '@shopify/polaris';
import { useToast } from '@shopify/app-bridge-react';
import JoditEditor from 'jodit-react';

const VARIABLE_GROUPS = [
  {
    title: 'Branding',
    variables: [
      { key: '{logo_url}', help: 'Company logo image URL' },
      { key: '{website}', help: 'Website from Label Settings' },
      { key: '{from_name}', help: 'Company / ship-from name' },
      { key: '{from_phone}', help: 'Ship-from phone' },
      { key: '{from_email}', help: 'Ship-from email' },
    ],
  },
  {
    title: 'Ship-from address',
    variables: [
      { key: '{from_street1}', help: 'Street address' },
      { key: '{from_city}', help: 'City' },
      { key: '{from_state}', help: 'State / province' },
      { key: '{from_zip}', help: 'ZIP / postal code' },
      { key: '{from_country}', help: 'Country' },
    ],
  },
  {
    title: 'Ship-to address',
    variables: [
      { key: '{to_name}', help: 'Recipient name' },
      { key: '{to_street1}', help: 'Street address' },
      { key: '{to_city}', help: 'City' },
      { key: '{to_state}', help: 'State / province' },
      { key: '{to_zip}', help: 'ZIP / postal code' },
      { key: '{to_country}', help: 'Country' },
      { key: '{to_phone}', help: 'Phone' },
      { key: '{to_email}', help: 'Email' },
    ],
  },
  {
    title: 'Order',
    variables: [
      { key: '{order_number}', help: 'Order number' },
      { key: '{order_date}', help: 'Order date' },
      { key: '{tracking_number}', help: 'Tracking number' },
      { key: '{bar_code}', help: 'Barcode image (base64)' },
      { key: '{total_price}', help: 'Order total' },
    ],
  },
  {
    title: 'Line items',
    variables: [
      { key: '{items}', help: 'All items summary' },
      { key: '{item_name}', help: 'First item name' },
      { key: '{item_price}', help: 'First item price' },
      { key: '{item_quantity}', help: 'First item quantity' },
      { key: '{item_weight}', help: 'First item weight (grams)' },
    ],
  },
];

const TemplateCreateEditPage = () => {
  const navigate = useNavigate();
  const { show } = useToast();
  const { id } = useParams();
  const editorRef = useRef(null);

  const [formState, setFormState] = useState({ name: '', content: '', type: '' });
  const [pageLoading, setPageLoading] = useState(Boolean(id));
  const [savingTemplate, setSavingTemplate] = useState(false);
  const [error, setError] = useState('');

  const loadTemplate = useCallback(async () => {
    if (!id) {
      setPageLoading(false);
      return;
    }

    setPageLoading(true);
    setError('');

    try {
      const response = await fetch(`/label-templates/${id}`);
      const data = await response.json();
      setFormState({
        name: data.name || '',
        content: data.content || '',
        type: data.type || '',
      });
    } catch (err) {
      const message = 'Failed to load template';
      setError(message);
      show(message, { duration: 2500, isError: true });
    } finally {
      setPageLoading(false);
    }
  }, [id, show]);

  useEffect(() => {
    loadTemplate();
  }, [loadTemplate]);

  const handleInputChange = useCallback((field) => (value) => {
    setFormState((prev) => ({ ...prev, [field]: value }));
  }, []);

  const handleEditorChange = useCallback((newContent) => {
    setFormState((prev) => ({ ...prev, content: newContent }));
  }, []);

  const editorConfig = useMemo(() => ({
    uploader: { insertImageAsBase64URI: true },
    height: 520,
    toolbarSticky: false,
    allowTabNavigation: true,
    spellcheck: true,
    toolbarAdaptive: false,
    buttons: 'bold,italic,underline,|,ul,ol,|,link,image,source,|,align,fontsize,brush',
    readonly: false,
    askBeforePasteHTML: false,
    askBeforePasteFromWord: false,
    pasteHTML: true,
    events: {
      afterInit: (editor) => {
        editorRef.current = editor;
      },
    },
  }), []);

  const insertTextAtCursor = useCallback((variable) => {
    const editor = editorRef.current;
    if (!editor) {
      show('Click inside the HTML editor first, then insert a variable.', {
        duration: 2500,
        isError: true,
      });
      return;
    }

    if (editor.getMode() === 1 && editor.selection) {
      editor.selection.insertHTML(` ${variable} `);
      setFormState((prev) => ({ ...prev, content: editor.value }));
      return;
    }

    const newValue = `${editor.value || ''} ${variable} `;
    editor.value = newValue;
    setFormState((prev) => ({ ...prev, content: newValue }));
  }, [show]);

  const saveTemplate = useCallback(async () => {
    if (!formState.name.trim() || !formState.content.trim()) {
      show('Template name and content are required.', { duration: 2500, isError: true });
      return;
    }

    setSavingTemplate(true);
    try {
      const requestOptions = {
        method: id ? 'PUT' : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formState),
      };
      const url = id ? `/label-templates/${id}` : '/label-templates';
      await fetch(`${url}?shop=${Config.shop}`, requestOptions);
      show('Template saved successfully!', { duration: 2000 });
      navigate('/pages/templates');
    } catch (err) {
      show(err.message || 'An unexpected error occurred.', { duration: 2500, isError: true });
    } finally {
      setSavingTemplate(false);
    }
  }, [formState, id, navigate, show]);

  if (pageLoading) {
    return (
      <Page
        backAction={{ content: 'Templates', onAction: () => navigate('/pages/templates') }}
        title={id ? 'Edit Template' : 'Create Template'}
      >
        <Box padding="500">
          <div style={{ display: 'flex', justifyContent: 'center' }}>
            <Spinner accessibilityLabel="Loading template editor" size="large" />
          </div>
        </Box>
      </Page>
    );
  }

  return (
    <Page
      fullWidth
      backAction={{ content: 'Templates', onAction: () => navigate('/pages/templates') }}
      title={id ? 'Edit Template' : 'Create Template'}
      titleMetadata={id ? <Badge tone="success">Editing</Badge> : null}
      primaryAction={{
        content: 'Save template',
        onAction: saveTemplate,
        loading: savingTemplate,
      }}
      secondaryActions={[
        {
          content: 'Label Settings',
          onAction: () => navigate('/pages/templates/label-settings'),
        },
      ]}
    >
      <div className="label-template-edit-page">
        {error ? (
          <Box paddingBlockEnd="4">
            <Banner status="critical" onDismiss={() => setError('')}>
              {error}
            </Banner>
          </Box>
        ) : null}

        <Layout>
          <Layout.Section>
            <LegacyCard sectioned title="Template details">
              <TextField
                label="Template name"
                value={formState.name}
                onChange={handleInputChange('name')}
                placeholder="Enter template name"
                autoComplete="off"
              />
            </LegacyCard>

            <Box paddingBlockStart="4">
              <LegacyCard sectioned title="HTML content">
                <Text as="p" variant="bodySm" tone="subdued">
                  Design your label HTML. Click any variable on the right to insert it at the cursor.
                </Text>
                <div className="label-template-editor">
                  <JoditEditor
                    ref={editorRef}
                    value={formState.content}
                    config={editorConfig}
                    onBlur={(newContent) => handleEditorChange(newContent)}
                  />
                </div>
              </LegacyCard>
            </Box>
          </Layout.Section>

          <Layout.Section oneThird>
            <LegacyCard title="Available variables">
              <LegacyCard.Section>
                <Text as="p" variant="bodySm" tone="subdued">
                  Click a variable to insert it into the HTML editor. Branding and ship-from values come from Label Settings.
                </Text>
              </LegacyCard.Section>

              {VARIABLE_GROUPS.map((group) => (
                <LegacyCard.Section key={group.title} title={group.title}>
                  <div className="label-template-variables">
                    {group.variables.map((variable) => (
                      <button
                        type="button"
                        key={variable.key}
                        className="label-template-variable-chip"
                        title={variable.help}
                        onClick={() => insertTextAtCursor(variable.key)}
                      >
                        <span className="label-template-variable-key">{variable.key}</span>
                        <span className="label-template-variable-help">{variable.help}</span>
                      </button>
                    ))}
                  </div>
                </LegacyCard.Section>
              ))}
            </LegacyCard>
          </Layout.Section>
        </Layout>
      </div>
    </Page>
  );
};

export default TemplateCreateEditPage;
