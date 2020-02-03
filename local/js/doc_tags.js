var SilverDocTags = BX.namespace('SilverDocTags');
SilverDocTags.createAddTagButton = function () {
	var sidbarEditor = BX.findChildByClassName(document, 'disk-detail-sidebar-editor',true);
	if (sidbarEditor) {
		var addTagButton = BX.create(
			'div',
			{
				attrs: {
					className: 'disk-detail-sidebar-editor-item disk-detail-sidebar-editor-item-addtag',
					id: 'bx-disk-filepage-addtag'
				},
				html: 'Добавить теги'
			}
		);
		BX.bind(addTagButton, 'click', function (){
			SilverDocTags.file_id = BX.Disk['FileViewClass_file_view_with_version'].object.id; // получаем id текущего файла
			BX.ajax.post(
				'/local/ajax/tags.php?method=getFileTags',
				{file: SilverDocTags.file_id},
				function (data) {
					SilverDocTags.currentTags = data
					console.log(data);
					SilverDocTags.showTagAddPopup();
				}
			);

		});
		sidbarEditor.appendChild(addTagButton);
	}
};

SilverDocTags.showTagAddPopup = function() {

	if (SilverDocTags.addTagPopup) {
		SilverDocTags.addTagPopup.close();
		SilverDocTags.addTagPopup.destroy();
	}

	SilverDocTags.addTagPopup = new BX.PopupWindow(
		'addDocsTag',
		null,
		{
			width: 600,
			height: 400,
			closeByEsc: true,
			closeIcon: true,
			overlay: {
				opacity: 50,
				backgroundColor: '#000'
			},
			titleBar: BX.message('ADD_DOC_TAG_POPUP_TITLE'),
			content: BX.create(
				'div',
				{
					attrs: {
						id: 'bx-disk-addtag-dialog'
					},
					html:
						'<strong>' + BX.message('ADD_DOC_TAG_POPUP_TAG_LIST') + '</strong><br>' +
						'<p>' + SilverDocTags.currentTags + '</p>' +
						'<p>' + BX.message('ADD_DOC_TAG_POPUP_DESCR') + '</p>' +
						'<input type="text" class="input-tag" name="tag" value="" onkeyup="javascript:SilverDocTags.findTags(this)"/>' +
						'<div class="disk-tag-variants"></div>'
				}
			),
			buttons: [
				new BX.PopupWindowButton({
					text: BX.message('ADD_DOC_TAG_POPUP_BUTTON'),
					className: 'popup-window-button-accept',
					events: {
						click: SilverDocTags.addTagToFile
					}
				})
			]
		}
	);
	SilverDocTags.addTagPopup.show();
};

SilverDocTags.findTags = function (b) {
	if(b.value.length > 2) {
		BX.ajax.post(
			'/local/ajax/tags.php?method=findTags',
			{data: b.value},
			SilverDocTags.showVariants
		);
	}
};

SilverDocTags.showVariants = function(data) {
	var tags = '';
	var json = JSON.parse(data)
	var i;
	for (i = 0; i < json.length; ++i) {
		tags += '<li><a href="#" onclick="SilverDocTags.chooseTag(this)">' + json[i] + '</a></li>';
	}
	var tagVariantsCont = BX.findChildByClassName(document, "disk-tag-variants", true);
	if(tagVariantsCont) {
		SilverDocTags.tagVariants = BX.create(
	 		'ul',
	 		{
	 			'html': tags
	 		}
	 	);
		BX.cleanNode(tagVariantsCont);
		tagVariantsCont.appendChild(SilverDocTags.tagVariants);
	}
	console.log(data);
};

SilverDocTags.notingFound = function() {
	console.log('f');
};
SilverDocTags.chooseTag = function (o) {
	var inputTag = BX.findChildByClassName(document, 'input-tag', true);
	if(inputTag) {
		inputTag.value = o.text;
	}
	SilverDocTags.tagVariants.remove();
}
SilverDocTags.addTagToFile = function () {
	var tag = BX.findChildByClassName(document, 'input-tag', true).value;
	console.log(SilverDocTags.file_id);
	console.log(tag);
	if (SilverDocTags.file_id && tag) {
		console.log('add');
		BX.ajax.post(
			'/local/ajax/tags.php?method=addTag',
			{tag: tag, file: SilverDocTags.file_id},
			function (data) {
				if(data == 'true') {
					SilverDocTags.addTagPopup.close();
					SilverDocTags.addTagPopup.destroy();
				}
			}
		);
	}
}