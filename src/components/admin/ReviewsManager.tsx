import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Star, Trash2, User, Clock, MessageSquare, TrendingUp } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';
import ApiConfig from '@/config/api';

interface Review {
  id: string;
  rating: number;
  feedback: string;
  rated_at: string;
  created_at: string;
  status: string;
  user_name: string;
  user_email: string;
  user_type: 'user' | 'guest';
  admin_username: string;
  admin_full_name: string;
  session_duration: number | null;
}

interface ReviewStats {
  total_reviews: number;
  average_rating: number;
  rating_breakdown: {
    [key: string]: number;
  };
}

interface ReviewsData {
  reviews: Review[];
  pagination: {
    total: number;
    limit: number;
    offset: number;
    has_more: boolean;
  };
  statistics: ReviewStats;
}

interface ReviewsManagerProps {
  isActive?: boolean;
}

const ReviewsManager: React.FC<ReviewsManagerProps> = ({ isActive = false }) => {
  const [reviewsData, setReviewsData] = useState<ReviewsData | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [ratingFilter, setRatingFilter] = useState<string>('all');
  const [sortBy, setSortBy] = useState<string>('rated_at');
  const [sortOrder, setSortOrder] = useState<string>('DESC');
  const { toast } = useToast();

  const fetchReviews = async () => {
    // Double-check that component is active before making API call
    if (!isActive) {
      return;
    }

    setIsLoading(true);
    try {
      const params = new URLSearchParams({
        limit: '50',
        offset: '0',
        sort_by: sortBy,
        sort_order: sortOrder,
      });

      if (ratingFilter !== 'all') {
        params.append('rating', ratingFilter);
      }

      const response = await fetch(`${ApiConfig.endpoints.admin.reviews}?${params}`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include session cookies for admin authentication
      });

      const data = await response.json();
      if (data.success) {
        setReviewsData(data.data);
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Fetch reviews error:', {
        error: error,
        message: error instanceof Error ? error.message : 'Unknown error',
        stack: error instanceof Error ? error.stack : undefined,
        endpoint: `${ApiConfig.endpoints.admin.reviews}?limit=50&offset=0&sort_by=${sortBy}&sort_order=${sortOrder}${ratingFilter !== 'all' ? `&rating=${ratingFilter}` : ''}`
      });
      toast({
        title: 'Error',
        description: 'Failed to fetch reviews. Please try again.',
        variant: 'destructive',
      });
    } finally {
      setIsLoading(false);
    }
  };

  const deleteReview = async (sessionId: string) => {
    try {
      const response = await fetch(ApiConfig.endpoints.admin.reviews, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
        },
        credentials: 'include', // Include session cookies for admin authentication
        body: JSON.stringify({
          session_id: sessionId,
        }),
      });

      const data = await response.json();
      if (data.success) {
        toast({
          title: 'Success',
          description: 'Review deleted successfully.',
        });
        fetchReviews();
      } else {
        throw new Error(data.error);
      }
    } catch (error) {
      console.error('Delete review error:', error);
      toast({
        title: 'Error',
        description: 'Failed to delete review. Please try again.',
        variant: 'destructive',
      });
    }
  };

  useEffect(() => {
    // Only fetch reviews if component is active
    if (!isActive) {
      return;
    }

    fetchReviews();
  }, [ratingFilter, sortBy, sortOrder, isActive]);

  const renderStars = (rating: number) => {
    return Array.from({ length: 5 }, (_, i) => (
      <Star
        key={i}
        className={`h-4 w-4 ${
          i < rating ? 'text-yellow-400 fill-current' : 'text-gray-300'
        }`}
      />
    ));
  };

  const formatDuration = (seconds: number | null) => {
    if (!seconds) return 'N/A';
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    
    if (hours > 0) {
      return `${hours}h ${minutes % 60}m`;
    }
    return `${minutes}m`;
  };

  const getRatingColor = (rating: number) => {
    if (rating >= 4) return 'bg-green-100 text-green-800';
    if (rating >= 3) return 'bg-yellow-100 text-yellow-800';
    return 'bg-red-100 text-red-800';
  };

  if (isLoading) {
    return (
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h2 className="text-2xl font-bold text-white">Customer Reviews</h2>
        </div>
        <div className="text-center py-8">
          <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
          <p className="text-gray-400 mt-2">Loading reviews...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h2 className="text-2xl font-bold text-white">Customer Reviews</h2>
        <Button onClick={fetchReviews} variant="outline" size="sm">
          Refresh
        </Button>
      </div>

      {/* Statistics Cards */}
      {reviewsData?.statistics && (
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center">
                <MessageSquare className="h-8 w-8 text-blue-400" />
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-400">Total Reviews</p>
                  <p className="text-2xl font-bold text-white">{reviewsData.statistics.total_reviews}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center">
                <TrendingUp className="h-8 w-8 text-green-400" />
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-400">Average Rating</p>
                  <div className="flex items-center">
                    <p className="text-2xl font-bold text-white mr-2">{reviewsData.statistics.average_rating}</p>
                    <div className="flex">{renderStars(Math.round(reviewsData.statistics.average_rating))}</div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center">
                <Star className="h-8 w-8 text-yellow-400" />
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-400">5-Star Reviews</p>
                  <p className="text-2xl font-bold text-white">{reviewsData.statistics.rating_breakdown['5']}</p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card className="bg-gray-800 border-gray-700">
            <CardContent className="p-4">
              <div className="flex items-center">
                <Star className="h-8 w-8 text-red-400" />
                <div className="ml-4">
                  <p className="text-sm font-medium text-gray-400">1-Star Reviews</p>
                  <p className="text-2xl font-bold text-white">{reviewsData.statistics.rating_breakdown['1']}</p>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Filters */}
      <Card className="bg-gray-800 border-gray-700">
        <CardContent className="p-4">
          <div className="flex flex-wrap gap-4">
            <div className="flex items-center space-x-2">
              <label className="text-sm font-medium text-gray-300">Filter by Rating:</label>
              <Select value={ratingFilter} onValueChange={setRatingFilter}>
                <SelectTrigger className="w-32 bg-gray-700 border-gray-600 text-white">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent className="bg-gray-700 border-gray-600">
                  <SelectItem value="all">All Ratings</SelectItem>
                  <SelectItem value="5">5 Stars</SelectItem>
                  <SelectItem value="4">4 Stars</SelectItem>
                  <SelectItem value="3">3 Stars</SelectItem>
                  <SelectItem value="2">2 Stars</SelectItem>
                  <SelectItem value="1">1 Star</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="flex items-center space-x-2">
              <label className="text-sm font-medium text-gray-300">Sort by:</label>
              <Select value={sortBy} onValueChange={setSortBy}>
                <SelectTrigger className="w-32 bg-gray-700 border-gray-600 text-white">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent className="bg-gray-700 border-gray-600">
                  <SelectItem value="rated_at">Date Rated</SelectItem>
                  <SelectItem value="rating">Rating</SelectItem>
                  <SelectItem value="created_at">Session Date</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="flex items-center space-x-2">
              <label className="text-sm font-medium text-gray-300">Order:</label>
              <Select value={sortOrder} onValueChange={setSortOrder}>
                <SelectTrigger className="w-32 bg-gray-700 border-gray-600 text-white">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent className="bg-gray-700 border-gray-600">
                  <SelectItem value="DESC">Newest First</SelectItem>
                  <SelectItem value="ASC">Oldest First</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Reviews List */}
      <Card className="bg-gray-800 border-gray-700">
        <CardHeader>
          <CardTitle className="text-white">Reviews ({reviewsData?.pagination.total || 0})</CardTitle>
        </CardHeader>
        <CardContent>
          {reviewsData?.reviews.length === 0 ? (
            <div className="text-center py-8">
              <MessageSquare className="h-12 w-12 text-gray-400 mx-auto mb-4" />
              <p className="text-gray-400">No reviews found.</p>
            </div>
          ) : (
            <div className="space-y-4">
              {reviewsData?.reviews.map((review) => (
                <div key={review.id} className="border border-gray-700 rounded-lg p-4">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center space-x-4 mb-2">
                        <div className="flex items-center">
                          <User className="h-4 w-4 text-gray-400 mr-1" />
                          <span className="text-white font-medium">{review.user_name}</span>
                          <Badge variant="outline" className="ml-2 text-xs">
                            {review.user_type}
                          </Badge>
                        </div>
                        <Badge className={getRatingColor(review.rating)}>
                          {review.rating} ⭐
                        </Badge>
                        <div className="flex items-center text-gray-400 text-sm">
                          <Clock className="h-4 w-4 mr-1" />
                          {formatDuration(review.session_duration)}
                        </div>
                      </div>
                      
                      <div className="flex mb-2">{renderStars(review.rating)}</div>
                      
                      {review.feedback && (
                        <p className="text-gray-300 mb-2">{review.feedback}</p>
                      )}
                      
                      <div className="flex items-center justify-between text-sm text-gray-400">
                        <div>
                          <span>Handled by: {review.admin_username || 'N/A'}</span>
                          {review.admin_full_name && (
                            <span className="ml-1">({review.admin_full_name})</span>
                          )}
                        </div>
                        <span>Rated: {new Date(review.rated_at).toLocaleDateString()}</span>
                      </div>
                    </div>
                    
                    <Button
                      onClick={() => deleteReview(review.id)}
                      size="sm"
                      variant="outline"
                      className="ml-4 border-red-200 text-red-600 hover:bg-red-50 hover:border-red-300"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
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

export default ReviewsManager;
