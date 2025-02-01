public function index()
{
    // These would typically come from your Instagram API or your own analytics storage.
    $followerCount = 1250;  
    $engagementRate = 3.5; // in percentage
    $totalPosts = 150;
    
    // Sample arrays for impressions over time
    $impressionsDates = ['2025-01-01', '2025-01-02', '2025-01-03', '2025-01-04'];
    $impressionsData = [150, 200, 170, 220];

    return view('instagram-analytics', compact('followerCount', 'engagementRate', 'totalPosts', 'impressionsDates', 'impressionsData'));
}
