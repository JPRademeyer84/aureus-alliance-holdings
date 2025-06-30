import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useToast } from '@/hooks/use-toast';
import { 
  Upload, 
  Image as ImageIcon, 
  FileText, 
  Plus, 
  Edit, 
  Trash2, 
  Eye,
  RefreshCw,
  Star,
  Settings
} from 'lucide-react';
import ApiConfig from '@/config/api';

interface CertificateTemplate {
  id: string;
  template_name: string;
  template_type: 'share_certificate' | 'nft_certificate' | 'dividend_certificate';
  frame_image_path?: string;
  background_image_path?: string;
  template_config: any;
  is_active: boolean;
  is_default: boolean;
  version: string;
  created_by_username: string;
  updated_by_username?: string;
  created_at: string;
  updated_at: string;
}

const CertificateTemplateManager: React.FC = () => {
  const [templates, setTemplates] = useState<CertificateTemplate[]>([]);
  const [loading, setLoading] = useState(true);
  const [showCreateDialog, setShowCreateDialog] = useState(false);
  const [editingTemplate, setEditingTemplate] = useState<CertificateTemplate | null>(null);
  const [formData, setFormData] = useState({
    template_name: '',
    template_type: 'share_certificate' as const,
    is_active: true,
    is_default: false,
    version: '1.0',
    template_config: {
      text: {
        certificate_number: { x: 100, y: 100, size: 16, color: [0, 0, 0] },
        holder_name: { x: 400, y: 300, size: 24, color: [0, 0, 0] },
        share_quantity: { x: 300, y: 400, size: 18, color: [0, 0, 0] },
        certificate_value: { x: 500, y: 400, size: 18, color: [0, 0, 0] },
        issue_date: { x: 400, y: 500, size: 14, color: [0, 0, 0] },
        package_name: { x: 400, y: 350, size: 16, color: [0, 0, 0] }
      },
      qr_code: { x: 50, y: 550 }
    }
  });
  const [uploadingFiles, setUploadingFiles] = useState(false);
  const { toast } = useToast();

  useEffect(() => {
    loadTemplates();
  }, []);

  const loadTemplates = async () => {
    try {
      const response = await fetch(`${ApiConfig.baseUrl}/admin/certificate-templates.php`);
      const data = await response.json();
      
      if (data.success) {
        setTemplates(data.templates || []);
      } else {
        throw new Error(data.error || 'Failed to load templates');
      }
    } catch (error) {
      console.error('Error loading templates:', error);
      toast({
        title: 'Error',
        description: 'Failed to load certificate templates',
        variant: 'destructive',
      });
    } finally {
      setLoading(false);
    }
  };

  const handleFileUpload = async (files: FileList, type: 'frame' | 'background') => {
    if (!files || files.length === 0) return null;

    const formData = new FormData();
    formData.append(`${type}_image`, files[0]);

    try {
      setUploadingFiles(true);
      const response = await fetch(`${ApiConfig.baseUrl}/admin/certificate-template-upload.php`, {
        method: 'POST',
        body: formData,
      });

      const data = await response.json();
      
      if (data.success) {
        toast({
          title: 'Success',
          description: `${type} image uploaded successfully`,
        });
        return data.files[`${type}_image_path`];
      } else {
        throw new Error(data.error || 'Upload failed');
      }
    } catch (error) {
      console.error('Error uploading file:', error);
      toast({
        title: 'Error',
        description: `Failed to upload ${type} image`,
        variant: 'destructive',
      });
      return null;
    } finally {
      setUploadingFiles(false);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    try {
      const method = editingTemplate ? 'PUT' : 'POST';
      const payload = {
        ...formData,
        created_by: 'admin', // Should be actual admin ID
        ...(editingTemplate && { id: editingTemplate.id, updated_by: 'admin' })
      };

      const response = await fetch(`${ApiConfig.baseUrl}/admin/certificate-templates.php`, {
        method,
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(payload),
      });

      const data = await response.json();
      
      if (data.success) {
        toast({
          title: 'Success',
          description: `Template ${editingTemplate ? 'updated' : 'created'} successfully`,
        });
        
        loadTemplates();
        setShowCreateDialog(false);
        setEditingTemplate(null);
        resetForm();
      } else {
        throw new Error(data.error || 'Failed to save template');
      }
    } catch (error) {
      console.error('Error saving template:', error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : 'Failed to save template',
        variant: 'destructive',
      });
    }
  };

  const handleDelete = async (templateId: string) => {
    if (!confirm('Are you sure you want to delete this template?')) return;

    try {
      const response = await fetch(`${ApiConfig.baseUrl}/admin/certificate-templates.php`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: templateId }),
      });

      const data = await response.json();
      
      if (data.success) {
        toast({
          title: 'Success',
          description: 'Template deleted successfully',
        });
        loadTemplates();
      } else {
        throw new Error(data.error || 'Failed to delete template');
      }
    } catch (error) {
      console.error('Error deleting template:', error);
      toast({
        title: 'Error',
        description: error instanceof Error ? error.message : 'Failed to delete template',
        variant: 'destructive',
      });
    }
  };

  const resetForm = () => {
    setFormData({
      template_name: '',
      template_type: 'share_certificate',
      is_active: true,
      is_default: false,
      version: '1.0',
      template_config: {
        text: {
          certificate_number: { x: 100, y: 100, size: 16, color: [0, 0, 0] },
          holder_name: { x: 400, y: 300, size: 24, color: [0, 0, 0] },
          share_quantity: { x: 300, y: 400, size: 18, color: [0, 0, 0] },
          certificate_value: { x: 500, y: 400, size: 18, color: [0, 0, 0] },
          issue_date: { x: 400, y: 500, size: 14, color: [0, 0, 0] },
          package_name: { x: 400, y: 350, size: 16, color: [0, 0, 0] }
        },
        qr_code: { x: 50, y: 550 }
      }
    });
  };

  const openEditDialog = (template: CertificateTemplate) => {
    setEditingTemplate(template);
    setFormData({
      template_name: template.template_name,
      template_type: template.template_type,
      is_active: template.is_active,
      is_default: template.is_default,
      version: template.version,
      template_config: template.template_config || {}
    });
    setShowCreateDialog(true);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <RefreshCw className="w-8 h-8 animate-spin" />
        <span className="ml-2">Loading templates...</span>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-3xl font-bold tracking-tight">Certificate Templates</h2>
          <p className="text-muted-foreground">
            Manage certificate templates for share certificates
          </p>
        </div>
        
        <Dialog open={showCreateDialog} onOpenChange={(open) => {
          setShowCreateDialog(open);
          if (!open) {
            setEditingTemplate(null);
            resetForm();
          }
        }}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="w-4 h-4 mr-2" />
              Create Template
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>
                {editingTemplate ? 'Edit Template' : 'Create New Template'}
              </DialogTitle>
              <DialogDescription>
                Configure the template for generating share certificates
              </DialogDescription>
            </DialogHeader>
            
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="template_name">Template Name</Label>
                  <Input
                    id="template_name"
                    value={formData.template_name}
                    onChange={(e) => setFormData(prev => ({ ...prev, template_name: e.target.value }))}
                    placeholder="e.g., Default Share Certificate"
                    required
                  />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="template_type">Template Type</Label>
                  <Select 
                    value={formData.template_type} 
                    onValueChange={(value: any) => setFormData(prev => ({ ...prev, template_type: value }))}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="share_certificate">Share Certificate</SelectItem>
                      <SelectItem value="nft_certificate">NFT Certificate</SelectItem>
                      <SelectItem value="dividend_certificate">Dividend Certificate</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="version">Version</Label>
                  <Input
                    id="version"
                    value={formData.version}
                    onChange={(e) => setFormData(prev => ({ ...prev, version: e.target.value }))}
                    placeholder="1.0"
                  />
                </div>
                
                <div className="space-y-4">
                  <div className="flex items-center space-x-2">
                    <Switch
                      id="is_active"
                      checked={formData.is_active}
                      onCheckedChange={(checked) => setFormData(prev => ({ ...prev, is_active: checked }))}
                    />
                    <Label htmlFor="is_active">Active Template</Label>
                  </div>
                  
                  <div className="flex items-center space-x-2">
                    <Switch
                      id="is_default"
                      checked={formData.is_default}
                      onCheckedChange={(checked) => setFormData(prev => ({ ...prev, is_default: checked }))}
                    />
                    <Label htmlFor="is_default">Default Template</Label>
                  </div>
                </div>
              </div>

              {/* File Upload Section */}
              <div className="space-y-4">
                <h3 className="text-lg font-semibold">Template Images</h3>
                
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Background Image</Label>
                    <div className="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                      <input
                        type="file"
                        accept="image/*"
                        onChange={(e) => e.target.files && handleFileUpload(e.target.files, 'background')}
                        className="hidden"
                        id="background-upload"
                      />
                      <label htmlFor="background-upload" className="cursor-pointer">
                        <ImageIcon className="w-8 h-8 mx-auto mb-2 text-gray-400" />
                        <p className="text-sm text-gray-600">Click to upload background</p>
                      </label>
                    </div>
                  </div>
                  
                  <div className="space-y-2">
                    <Label>Frame Image</Label>
                    <div className="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                      <input
                        type="file"
                        accept="image/*"
                        onChange={(e) => e.target.files && handleFileUpload(e.target.files, 'frame')}
                        className="hidden"
                        id="frame-upload"
                      />
                      <label htmlFor="frame-upload" className="cursor-pointer">
                        <ImageIcon className="w-8 h-8 mx-auto mb-2 text-gray-400" />
                        <p className="text-sm text-gray-600">Click to upload frame</p>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              <div className="flex justify-end space-x-2">
                <Button 
                  type="button" 
                  variant="outline" 
                  onClick={() => setShowCreateDialog(false)}
                >
                  Cancel
                </Button>
                <Button type="submit" disabled={uploadingFiles}>
                  {uploadingFiles ? (
                    <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                  ) : null}
                  {editingTemplate ? 'Update' : 'Create'} Template
                </Button>
              </div>
            </form>
          </DialogContent>
        </Dialog>
      </div>

      {/* Templates Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {templates.map((template) => (
          <Card key={template.id} className="relative">
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle className="text-lg">{template.template_name}</CardTitle>
                <div className="flex items-center gap-2">
                  {template.is_default && (
                    <Badge variant="default" className="flex items-center gap-1">
                      <Star className="w-3 h-3" />
                      Default
                    </Badge>
                  )}
                  {template.is_active ? (
                    <Badge variant="default">Active</Badge>
                  ) : (
                    <Badge variant="secondary">Inactive</Badge>
                  )}
                </div>
              </div>
              <CardDescription>
                {template.template_type.replace('_', ' ').toUpperCase()} • Version {template.version}
              </CardDescription>
            </CardHeader>
            
            <CardContent>
              <div className="space-y-4">
                <div className="text-sm text-muted-foreground">
                  <p>Created by: {template.created_by_username}</p>
                  <p>Created: {new Date(template.created_at).toLocaleDateString()}</p>
                  {template.updated_by_username && (
                    <p>Updated by: {template.updated_by_username}</p>
                  )}
                </div>
                
                <div className="flex items-center gap-2">
                  <Button variant="outline" size="sm" onClick={() => openEditDialog(template)}>
                    <Edit className="w-4 h-4" />
                  </Button>
                  <Button variant="outline" size="sm">
                    <Eye className="w-4 h-4" />
                  </Button>
                  <Button 
                    variant="outline" 
                    size="sm" 
                    onClick={() => handleDelete(template.id)}
                    className="text-red-600 hover:text-red-700"
                  >
                    <Trash2 className="w-4 h-4" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
      
      {templates.length === 0 && (
        <Card>
          <CardContent className="text-center py-8">
            <FileText className="w-12 h-12 mx-auto mb-4 text-muted-foreground" />
            <h3 className="text-lg font-semibold mb-2">No Templates Found</h3>
            <p className="text-muted-foreground mb-4">
              Create your first certificate template to get started
            </p>
            <Button onClick={() => setShowCreateDialog(true)}>
              <Plus className="w-4 h-4 mr-2" />
              Create Template
            </Button>
          </CardContent>
        </Card>
      )}
    </div>
  );
};

export default CertificateTemplateManager;
