@extends('layouts.frontend')

@section('title', 'Kontrol Paneli')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-3">
            @include('frontend.partials.sidebar')
        </div>
        
        <div class="col-md-9">
            <div class="dashboard-header">
                <h2>Hoş Geldiniz, {{ auth()->user()->username }}</h2>
                <p>Bakiye: {{ format_money(auth()->user()->balance) }}</p>
            </div>
            
            <div class="stats-cards">
                <div class="card">
                    <h3>Toplam URL</h3>
                    <p>{{ $userUrlsCount }}</p>
                </div>
                
                <div class="card">
                    <h3>Toplam Tıklama</h3>
                    <p>{{ $userClicksCount }}</p>
                </div>
                
                <div class="card">
                    <h3>Toplam Kazanç</h3>
                    <p>{{ format_money($userRevenue) }}</p>
                </div>
            </div>
            
            <div class="recent-urls">
                <h3>Son URL'leriniz</h3>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kısa URL</th>
                            <th>Orijinal URL</th>
                            <th>Tıklamalar</th>
                            <th>Kazanç</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentUrls as $url)
                        <tr>
                            <td>
                                <a href="{{ url($url->short_code) }}" target="_blank">
                                    {{ url($url->short_code) }}
                                </a>
                            </td>
                            <td class="truncate">{{ $url->original_url }}</td>
                            <td>{{ $url->statistics_count }}</td>
                            <td>{{ format_money($url->revenue) }}</td>
                            <td>
                                <a href="{{ route('url.stats', $url->short_code) }}" class="btn btn-sm btn-info">
                                    İstatistikler
                                </a>
                                <button class="btn btn-sm btn-danger delete-url" data-id="{{ $url->id }}">
                                    Sil
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <a href="{{ route('url.create') }}" class="btn btn-primary">
                    Yeni URL Ekle
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('.delete-url').click(function() {
        if (confirm('Bu URL\'yi silmek istediğinize emin misiniz?')) {
            const urlId = $(this).data('id');
            
            $.ajax({
                url: '/url/' + urlId,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    });
});
</script>
@endsection