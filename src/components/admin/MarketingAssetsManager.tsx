import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { useToast } from '@/hooks/use-toast';
import {
  Upload,
  FileText,
  Download,
  Trash2,
  Plus,
  Eye
} from '@/components/SafeIcons';

// Safe media icons
const Image = ({ className }: { className?: string }) => <span className={className}>🖼️</span>;
const Video = ({ className }: { className?: string }) => <span className={className}>🎥</span>;

interface MarketingAsset {
  id: string;
  type: 'image' | 'video' | 'banner' | 'logo' | 'document';
  title: string;
  description: string;
  url: string;
  size: string;
  format: string;
  status: 'active' | 'inactive' | 'deleted';
  download_count: number;
  created_at: string;
}

const MarketingAssetsManager: React.FC = () => {
  const [assets, setAssets] = useState<MarketingAsset[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [showUploadForm, setShowUploadForm] = useState(false);
  const [uploadForm, setUploadForm] = useState({
    type: 'image' as MarketingAsset['type'],
    title: '',
    description: '',
    file_url: '',
    file_size: '',
    file_format: ''
  });
  const { toast } = useToast();

  const fetchAssets = async () => {
    setIsLoading(true);
    try {
      const response = await fetch('http://localhost/aureus-angel-alliance/api/admin/marketing-assets.php', {
        credentials: 'include'
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setAssets(data.assets || []);
        } else {
          throw new Error(data.message || 'Failed to fetch marketing assets');
        }
      } else {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
    } catch (error) {
      console.error('Failed to fetch marketing assets:', error);
      setAssets([]); // Set empty array on error
      toast({
        title: "Error",
        description: "Failed to load marketing assets",
        variant: "destructive"
      });
    } finally {
      setIsLoading(false);
    }
  };

  const uploadAsset = async () => {
    if (!uploadForm.title || !uploadForm.file_url) {
      toast({
        title: "Validation Error",
        description: "Please fill in all required fields",
        variant: "destructive"
      });
      return;
    }

    try {
      const response = await fetch('/api/admin/marketing-assets', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(uploadForm)
      });

      if (response.ok) {
        const data = await response.json();
        setAssets([data.asset, ...assets]);
        setUploadForm({
          type: 'image',
          title: '',
          description: '',
          file_url: '',
          file_size: '',
          file_format: ''
        });
        setShowUploadForm(false);
        toast({
          title: "Success",
          description: "Marketing asset uploaded successfully",
        });
      } else {
        throw new Error('Upload failed');
      }
    } catch (error) {
      console.error('Failed to upload asset:', error);
      toast({
        title: "Upload Failed",
        description: "Could not upload marketing asset",
        variant: "destructive"
      });
    }
  };

  const deleteAsset = async (assetId: string) => {
    try {
      const response = await fetch(`/api/admin/marketing-assets/${assetId}`, {
        method: 'DELETE'
      });

      if (response.ok) {
        setAssets(assets.filter(asset => asset.id !== assetId));
        toast({
          title: "Success",
          description: "Marketing asset deleted successfully",
        });
      } else {
        throw new Error('Delete failed');
      }
    } catch (error) {
      console.error('Failed to delete asset:', error);
      toast({
        title: "Delete Failed",
        description: "Could not delete marketing asset",
        variant: "destructive"
      });
    }
  };

  useEffect(() => {
    fetchAssets();
  }, []);

  const getAssetIcon = (type: string) => {
    switch (type) {
      case 'image':
      case 'banner':
        return <Image className="h-5 w-5" />;
      case 'video':
        return <Video className="h-5 w-5" />;
      case 'logo':
        return <Image className="h-5 w-5" />;
      default:
        return <FileText className="h-5 w-5" />;
    }
  };

  const getTypeColor = (type: string) => {
    switch (type) {
      case 'image':
        return 'bg-blue-500/20 text-blue-400 border-blue-500/30';
      case 'video':
        return 'bg-red-500/20 text-red-400 border-red-500/30';
      case 'banner':
        return 'bg-green-500/20 text-green-400 border-green-500/30';
      case 'logo':
        return 'bg-purple-500/20 text-purple-400 border-purple-500/30';
      default:
        return 'bg-gray-500/20 text-gray-400 border-gray-500/30';
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-white">Marketing Assets Manager</h2>
          <p className="text-gray-400">Upload and manage marketing materials for affiliates</p>
        </div>
        <Button
          onClick={() => setShowUploadForm(!showUploadForm)}
          className="bg-gold-gradient text-black"
        >
          <Plus className="h-4 w-4 mr-2" />
          Upload Asset
        </Button>
      </div>

      {/* Upload Form */}
      {showUploadForm && (
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Upload className="h-5 w-5 text-gold" />
              Upload New Marketing Asset
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="text-sm text-gray-400 mb-2 block">Asset Type</label>
                <select
                  value={uploadForm.type}
                  onChange={(e) => setUploadForm({...uploadForm, type: e.target.value as MarketingAsset['type']})}
                  className="w-full bg-gray-700 border-gray-600 text-white rounded-md p-2"
                >
                  <option value="image">Image</option>
                  <option value="video">Video</option>
                  <option value="banner">Banner</option>
                  <option value="logo">Logo</option>
                  <option value="document">Document</option>
                </select>
              </div>
              <div>
                <label className="text-sm text-gray-400 mb-2 block">Title *</label>
                <Input
                  value={uploadForm.title}
                  onChange={(e) => setUploadForm({...uploadForm, title: e.target.value})}
                  placeholder="Asset title"
                  className="bg-gray-700 border-gray-600 text-white"
                />
              </div>
            </div>

            <div>
              <label className="text-sm text-gray-400 mb-2 block">Description</label>
              <Textarea
                value={uploadForm.description}
                onChange={(e) => setUploadForm({...uploadForm, description: e.target.value})}
                placeholder="Asset description"
                className="bg-gray-700 border-gray-600 text-white"
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label className="text-sm text-gray-400 mb-2 block">File URL *</label>
                <Input
                  value={uploadForm.file_url}
                  onChange={(e) => setUploadForm({...uploadForm, file_url: e.target.value})}
                  placeholder="https://example.com/asset.jpg"
                  className="bg-gray-700 border-gray-600 text-white"
                />
              </div>
              <div>
                <label className="text-sm text-gray-400 mb-2 block">File Size</label>
                <Input
                  value={uploadForm.file_size}
                  onChange={(e) => setUploadForm({...uploadForm, file_size: e.target.value})}
                  placeholder="1920x1080"
                  className="bg-gray-700 border-gray-600 text-white"
                />
              </div>
              <div>
                <label className="text-sm text-gray-400 mb-2 block">Format</label>
                <Input
                  value={uploadForm.file_format}
                  onChange={(e) => setUploadForm({...uploadForm, file_format: e.target.value})}
                  placeholder="JPG, PNG, MP4, etc."
                  className="bg-gray-700 border-gray-600 text-white"
                />
              </div>
            </div>

            <div className="flex items-center gap-2">
              <Button onClick={uploadAsset} className="bg-gold-gradient text-black">
                <Upload className="h-4 w-4 mr-2" />
                Upload Asset
              </Button>
              <Button variant="outline" onClick={() => setShowUploadForm(false)}>
                Cancel
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Assets List */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">Marketing Assets ({assets.length})</CardTitle>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="flex items-center justify-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-gold"></div>
            </div>
          ) : assets.length === 0 ? (
            <div className="text-center py-8">
              <Upload className="h-12 w-12 text-gray-600 mx-auto mb-4" />
              <p className="text-gray-400">No marketing assets uploaded yet</p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {assets.map((asset) => (
                <div key={asset.id} className="bg-gray-700/50 rounded-lg p-4">
                  <div className="flex items-start justify-between mb-3">
                    <div className="flex items-center gap-2">
                      {getAssetIcon(asset.type)}
                      <div>
                        <h3 className="text-white font-semibold text-sm">{asset.title}</h3>
                        <Badge className={`${getTypeColor(asset.type)} text-xs`}>
                          {asset.type}
                        </Badge>
                      </div>
                    </div>
                    <div className="flex items-center gap-1">
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => window.open(asset.url, '_blank')}
                      >
                        <Eye className="h-3 w-3" />
                      </Button>
                      <Button
                        size="sm"
                        variant="outline"
                        onClick={() => deleteAsset(asset.id)}
                        className="text-red-400 hover:text-red-300"
                      >
                        <Trash2 className="h-3 w-3" />
                      </Button>
                    </div>
                  </div>

                  <p className="text-gray-400 text-xs mb-2">{asset.description}</p>
                  
                  <div className="flex items-center justify-between text-xs text-gray-500">
                    <span>{asset.format} • {asset.size}</span>
                    <span>{asset.download_count} downloads</span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default MarketingAssetsManager;
