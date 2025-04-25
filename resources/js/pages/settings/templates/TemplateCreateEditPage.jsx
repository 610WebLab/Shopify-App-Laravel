import React, { useState, useEffect, useRef, useCallback } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { Page, Layout, Card, TextField, Badge, Toast, Frame, Spinner, Button, Text } from "@shopify/polaris";
import JoditEditor from "jodit-react"; // ✅ Import Jodit

const TemplateCreateEditPage = () => {
  const [formState, setFormState] = useState({ name: "", content: "", type: "" });
  const [loading, setLoading] = useState(false);
  const [toastContent, setToastContent] = useState(null);
  const [toastActive, setToastActive] = useState(false);
  const navigate = useNavigate();

  const { id } = useParams();
  const editorRef = useRef(null); // ✅ Jodit editor ref
  const selectionRangeRef = useRef(null);
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

      let url = id ? `/label-templates/${id}` : `/label-templates`

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
    uploader: { insertImageAsBase64URI: true },
    height: 400,
    toolbarSticky: false,
    allowTabNavigation: true,
    spellcheck: true,
    toolbarAdaptive: false,
    buttons: "bold,italic,underline,|,ul,ol,|,link,image,source",
    readonly: false,
    askBeforePasteHTML: false,
    askBeforePasteFromWord: false,
    pasteHTML: true,
    disablePlugins: [],
    events: {
      afterInit: (editor) => {
        editorRef.current = editor;
        console.log("Editor initialized:", editor);

        editor.events.on("paste", (e) => {
          console.log("Paste triggered", e);
        });
      },
    },
  };

  // ✅ Variables for insertion
  const templateVariables = [
    "{from_name}", "{from_street1}", "{from_city}", "{from_state}",
    "{from_zip}", "{from_country}", "{from_phone}", "{from_email}", "{to_name}",
    "{to_street1}", "{to_city}", "{to_state}", "{to_zip}", "{to_country}",
    "{to_phone}", "{to_email}", "{order_number}", "{order_date}", "{bar_code}", "{item_name}", "{item_price}",
    "{item_quantity}", "{item_weight}", "{total_price}"
  ];
  // useEffect(() => {
  //   if (editorRef.current && editorRef.current.editor) {
  //     joditInstance.current = editorRef.current.editor;
  //   }
  // }, []);

  const updateContent = (newContent) => {
    // setContent(value);
    setFormState((prev) => ({ ...prev, content: newContent }));
    try {
      const selection = window.getSelection();
      if (selection && selection.rangeCount > 0) {
        setPos(selection.getRangeAt(0).startOffset);
      }
    } catch (err) {
      console.warn("Error getting selection:", err);
    }
  };
  const insertTextAtCursor = (newContent) => {
    const editor = editorRef.current;
    if (!editor) return;

    const isWYSIWYG = editor.getMode() == 1;

    if (isWYSIWYG) {
      const sel = editor?.selection?.sel;
      if (sel && sel.rangeCount > 0) {
        const range = sel.getRangeAt(0);
        const textNode = document.createTextNode(` ${newContent} `);
        range.deleteContents();
        range.insertNode(textNode);
        range.setStartAfter(textNode);
        range.setEndAfter(textNode);
        sel.removeAllRanges();
        sel.addRange(range);

        // Update content in formState
        setFormState((prev) => ({ ...prev, content: editor.value }));
      } else {
        alert("Please click inside the editor first before inserting text.");
      }
    } else {
      // Source code mode
      const currentValue = editor.value;
      const newValue = currentValue + ` ${newContent} `;
      editor.value = newValue;
      setFormState((prev) => ({ ...prev, content: newValue }));
    }
  };



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
                <label style={{ fontWeight: "bold", display: "block", marginBottom: "10px" }}>Template Name </label>
                <TextField
                  // label="Template Name"
                  value={formState.name}
                  onChange={handleInputChange("name")}
                  placeholder="Enter template name"
                  autoComplete="off"
                />

                {/* ✅ Jodit Editor with Drag & Drop */}
                <div style={{ marginTop: "20px", minHeight: "400px" }}>
                  <label style={{ fontWeight: "bold", display: "block", marginBottom: "10px" }}>
                    HTML Content
                  </label>
                  <JoditEditor
                    ref={editorRef}
                    value={formState.content}
                    config={editorConfig}
                    // onInit={(instance) => {
                    //   joditInstance.current = instance;  // capture the actual Jodit instance here
                    // }}
                    onBlur={(newContent) => handleEditorChange(newContent)}
                  // onChange={(newContent) => updateContent(newContent)}
                  />
                </div>

                {/* ✅ Insert Variables into Editor */}
                <label style={{ fontWeight: "bold", display: "block", marginBottom: "10px", marginTop: "20px" }}>
                  Available Variables:
                </label>
                <Card sectioned>

                  <div style={{ display: "flex", flexWrap: "wrap", gap: "12px" }}>
                    {templateVariables.map((variable, index) => (
                      <div style={{ width: '15%' }} key={index}>
                        <Text variant="headingSm" as="p" fontWeight="regular">
                          {variable}
                        </Text>
                      </div>
                    ))}
                  </div>
                </Card>
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
