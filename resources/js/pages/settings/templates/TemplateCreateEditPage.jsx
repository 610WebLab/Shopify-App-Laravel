import React, { useState, useEffect, useRef, useCallback } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { Page, Layout, Card, TextField, Badge, Toast, Frame, Spinner, Button } from "@shopify/polaris";
import JoditEditor from "jodit-react"; // ✅ Import Jodit

const TemplateCreateEditPage = () => {
  const [formState, setFormState] = useState({ name: "", content: "", type: "" });
  const [loading, setLoading] = useState(false);
  const [toastContent, setToastContent] = useState(null);
  const [toastActive, setToastActive] = useState(false);
  const navigate = useNavigate();
  const { id } = useParams();
  const editorRef = useRef(null); // ✅ Jodit editor ref

  const toggleToastActive = useCallback(() => setToastActive((active) => !active), []);

  useEffect(() => {
    if (id) fetchTemplate();
  }, [id]);

  const fetchTemplate = async () => {
    setLoading(true);
    try {
      const response = await fetch(`/label-templates/${id}`);
      const data = await response.json();
      setFormState({ name: data.name, content: data.content, type: data.type });
    } catch (error) {
      showToast("Error fetching template.", "critical");
    } finally {
      setLoading(false);
    }
  };

  const handleInputChange = (field) => (value) => {
    setFormState((prev) => ({ ...prev, [field]: value }));
  };

  const handleEditorChange = (newContent) => {
    setFormState((prev) => ({ ...prev, content: newContent }));
  };

  const showToast = (message, tone = "info") => {
    setToastContent({ message, tone });
    setToastActive(true);
  };

  const saveTemplate = async () => {
    if (!formState.name.trim() || !formState.content.trim()) {
      showToast("Template name and content are required.", "critical");
      return;
    }

    setLoading(true);
    try {
      const requestOptions = {
        method: id ? "PUT" : "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(formState),
      };

      let url = id?`/label-templates/${id}`:`/label-templates`

      await fetch(`${url}?shop=${Config.shop}`, requestOptions);
      showToast("Template saved successfully!", "success");
      navigate("/pages/templates")
    } catch (error) {
      showToast(error.message || "An unexpected error occurred.", "critical");
    } finally {
      setLoading(false);
    }
  };

  // ✅ Jodit Editor Configuration
  const editorConfig = {
    uploader: { insertImageAsBase64URI: true }, // Enable image uploads as Base64 (No server needed)
    height: 400,
    toolbarSticky: false,
    allowTabNavigation: true,
    spellcheck: true,
    toolbarAdaptive: false,
    buttons: "bold,italic,underline,strikethrough,|,ul,ol,|,outdent,indent,|,image,link,table,|,hr,undo,redo,source",
  };

  // ✅ Variables for insertion
  const templateVariables = [
    "{from_name}", "{from_street1}", "{from_city}", "{from_state}",
    "{from_zip}", "{from_country}", "{from_phone}", "{from_email}", "{to_name}",
    "{to_street1}", "{to_city}", "{to_state}", "{to_zip}", "{to_country}",
    "{to_phone}", "{to_email}", "{order_number}","{order_date}","{bar_code}", "{item_name}", "{item_price}",
    "{item_quantity}", "{item_weight}", "{total_price}"
  ];

  return (
    <Frame>
      <Page
        backAction={{ content: "Templates", onAction: () => navigate("/pages/templates") }}
        title={id ? "Edit Template" : "Create Template"}
        titleMetadata={id && <Badge tone="success">Editing</Badge>}
        primaryAction={{ content: "Save", onAction: saveTemplate, disabled: loading }}
      >
        {loading ? (
          <Spinner accessibilityLabel="Loading..." size="large" />
        ) : (
          <Layout>
            <Layout.Section>
              <Card sectioned title="Template Details">
                <TextField
                  label="Template Name"
                  value={formState.name}
                  onChange={handleInputChange("name")}
                  placeholder="Enter template name"
                  autoComplete="off"
                />

                {/* ✅ Insert Variables into Editor */}
                <Card sectioned>
                  <h3>Available Variables:</h3>
                  <div style={{ display: "flex", flexWrap: "wrap", gap: "10px" }}>
                    {templateVariables.map((variable, index) => (
                      <Button key={index} onClick={() => handleEditorChange(formState.content + variable)}>
                        {variable}
                      </Button>
                    ))}
                  </div>
                </Card>

                {/* ✅ Jodit Editor with Drag & Drop */}
                <div style={{ marginTop: "20px", minHeight: "400px" }}>
                  <label style={{ fontWeight: "bold", display: "block", marginBottom: "10px" }}>
                    HTML Content
                  </label>
                  <JoditEditor
                    ref={editorRef}
                    value={formState.content}
                    config={editorConfig}
                    onBlur={(newContent) => handleEditorChange(newContent)}
                  />
                </div>
              </Card>
            </Layout.Section>
          </Layout>
        )}
      </Page>

      {toastActive && <Toast content={toastContent?.message} onDismiss={toggleToastActive} tone={toastContent?.tone} />}
    </Frame>
  );
};

export default TemplateCreateEditPage;
